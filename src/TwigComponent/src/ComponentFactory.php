<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\CompiledEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface as IntrospectableEventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyWriteInfo;
use Symfony\Component\PropertyInfo\PropertyWriteInfoExtractorInterface;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\UX\TwigComponent\Event\PostMountEvent;
use Symfony\UX\TwigComponent\Event\PreMountEvent;
use Twig\Environment;
use Twig\Extra\Html\HtmlAttr\MergeableInterface;
use Twig\Runtime\EscaperRuntime;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class ComponentFactory implements ResetInterface
{
    private array $mountMethods = [];
    private array $writableProperties = [];
    /** @var array<class-string, array<array-key, array<int, bool>>> */
    private array $directlyWritableProperties = [];
    private ?EscaperRuntime $escaper = null;

    /** @var array<string, ComponentMetadata> */
    private array $metadata = [];

    /**
     * Set when the dispatcher can be asked whether an event has listeners,
     * allowing to skip creating and dispatching events nobody listens to.
     */
    private readonly ?IntrospectableEventDispatcherInterface $introspectableDispatcher;

    /**
     * Whether to count the listeners of an event, to find the ones added at runtime.
     *
     * Adding listeners to a compiled dispatcher is deprecated since Symfony 8.2, and reading them would thaw it.
     */
    private readonly bool $countsRuntimeListeners;

    /**
     * Set when props can be written without the property accessor.
     *
     * It is the write info extractor the property accessor uses, so both pick the same mutator.
     */
    private readonly ?PropertyWriteInfoExtractorInterface $writeInfoExtractor;

    /**
     * @param array<string, array>                                                       $config
     * @param array<class-string, string>                                                $classMap
     * @param array<class-string, array{0: string|null, 1: array<string, true>, 2: int}> $listenerScopes For the events whose listeners all only act on some components:
     *                                                                                                   the metadata key and the props of those components, and the number of listeners
     */
    public function __construct(
        private ComponentTemplateFinderInterface $componentTemplateFinder,
        private ContainerInterface $components,
        private PropertyAccessorInterface $propertyAccessor,
        private EventDispatcherInterface $eventDispatcher,
        private array $config,
        private readonly array $classMap,
        private readonly Environment $twig,
        private readonly array $listenerScopes = [],
        ?PropertyWriteInfoExtractorInterface $writeInfoExtractor = null,
    ) {
        // A decorated or replaced accessor must keep receiving every write
        $this->writeInfoExtractor = PropertyAccessor::class === $propertyAccessor::class ? $writeInfoExtractor : null;
        $this->introspectableDispatcher = $eventDispatcher instanceof IntrospectableEventDispatcherInterface ? $eventDispatcher : null;
        $this->countsRuntimeListeners = !$eventDispatcher instanceof CompiledEventDispatcher;
    }

    public function metadataFor(string $name): ComponentMetadata
    {
        if ($metadata = $this->metadata[$name] ?? null) {
            return $metadata;
        }

        if ($config = $this->config[$name] ?? null) {
            return $this->metadata[$name] = new ComponentMetadata($config);
        }

        if ($template = $this->componentTemplateFinder->findAnonymousComponentTemplate($name)) {
            $this->config[$name] = [
                'key' => $name,
                'template' => $template,
            ];

            return $this->metadata[$name] = new ComponentMetadata($this->config[$name]);
        }

        if ($mappedName = $this->classMap[$name] ?? null) {
            if ($config = $this->config[$mappedName] ?? null) {
                return $this->metadata[$name] = $this->metadata[$mappedName] ??= new ComponentMetadata($config);
            }

            throw new \InvalidArgumentException(\sprintf('Unknown component "%s".', $name));
        }

        $this->throwUnknownComponentException($name);
    }

    /**
     * Creates the component and "mounts" it with the passed data.
     */
    public function create(string $name, array $data = []): MountedComponent
    {
        $metadata = $this->metadataFor($name);

        if ($metadata->isAnonymous()) {
            return $this->mountFromObject(new AnonymousComponent(), $data, $metadata);
        }

        return $this->mountFromObject($this->components->get($metadata->getName()), $data, $metadata);
    }

    /**
     * @internal
     */
    public function mountFromObject(object $component, array $data, ComponentMetadata $componentMetadata): MountedComponent
    {
        $originalData = $data;
        $dispatchPreMount = $this->hasListenersFor(PreMountEvent::class, $componentMetadata, $data);
        $dispatchPostMount = $this->hasListenersFor(PostMountEvent::class, $componentMetadata, $data);

        // Without mount listeners, nothing can change the data of an anonymous component: it has no hooks and no properties
        if (!$dispatchPreMount && !$dispatchPostMount && $component instanceof AnonymousComponent) {
            $component->mount($data);
            $extraMetadata = [];
        } else {
            $this->preMount($component, $data, $componentMetadata, $dispatchPreMount);

            $this->mount($component, $data, $componentMetadata);

            if (!$componentMetadata->isAnonymous()) {
                // set data that wasn't set in mount on the component directly
                foreach ($data as $property => $value) {
                    if ($this->writableProperties[$componentMetadata->getName()][$property] ??= $this->propertyAccessor->isWritable($component, $property)) {
                        $this->writeProperty($component, $property, $value);
                        unset($data[$property]);
                    }
                }
            }

            $extraMetadata = $this->postMount($component, $data, $componentMetadata, $dispatchPostMount);
        }

        // create attributes from "attributes" key if exists
        $attributesVar = $componentMetadata->getAttributesVar();
        $attributes = $data[$attributesVar] ?? [];
        unset($data[$attributesVar]);

        foreach ($data as $key => $value) {
            if ($value instanceof \Stringable && !$value instanceof MergeableInterface) {
                $data[$key] = (string) $value;
            }
        }

        return new MountedComponent(
            $componentMetadata->getName(),
            $component,
            new ComponentAttributes([...$attributes, ...$data], $this->escaper ??= $this->twig->getRuntime(EscaperRuntime::class)),
            $originalData,
            $extraMetadata,
        );
    }

    /**
     * Returns the "unmounted" component.
     *
     * @internal
     */
    public function get(string $name): object
    {
        $metadata = $this->metadataFor($name);

        if ($metadata->isAnonymous()) {
            return new AnonymousComponent();
        }

        return $this->components->get($metadata->getName());
    }

    /**
     * Whether the given event has a listener that may act on the given component.
     *
     * @internal
     *
     * @param class-string         $eventClass
     * @param array<string, mixed> $props      The props the component is rendered with
     */
    public function hasListenersFor(string $eventClass, string|ComponentMetadata $component, array $props): bool
    {
        if (null === $this->introspectableDispatcher) {
            return true;
        }

        if ($scope = $this->listenerScopes[$eventClass] ?? null) {
            [$scopedMetadataKey, $scopedProps, $listenerCount] = $scope;
            $inScope = $scopedProps && array_intersect_key($props, $scopedProps);

            if (!$inScope && null !== $scopedMetadataKey) {
                $metadata = $component instanceof ComponentMetadata ? $component : $this->metadataFor($component);
                $inScope = $metadata->get($scopedMetadataKey, false);
            }

            if (!$inScope) {
                // Listeners registered at compile time are out of scope, but those added at runtime may not be.
                return $this->countsRuntimeListeners && \count($this->introspectableDispatcher->getListeners($eventClass)) > $listenerCount;
            }
        }

        return $this->introspectableDispatcher->hasListeners($eventClass);
    }

    private function mount(object $component, array &$data, ComponentMetadata $componentMetadata): void
    {
        if ($component instanceof AnonymousComponent) {
            $component->mount($data);

            return;
        }

        if (!$componentMetadata->getMounts()) {
            return;
        }

        $mount = $this->mountMethods[$component::class] ??= new \ReflectionClass($component)->getMethod('mount');

        $parameters = [];
        foreach ($mount->getParameters() as $refParameter) {
            if (\array_key_exists($name = $refParameter->getName(), $data)) {
                $parameters[] = $data[$name];
                // remove the data element so it isn't used to set the property directly.
                unset($data[$name]);
            } elseif ($refParameter->isDefaultValueAvailable()) {
                $parameters[] = $refParameter->getDefaultValue();
            } else {
                throw new \LogicException(\sprintf('"%s" has a required $%s parameter. Make sure to pass it or give it a default value.', $component::class.'::mount()', $name));
            }
        }

        $mount->invoke($component, ...$parameters);
    }

    private function preMount(object $component, array &$data, ComponentMetadata $componentMetadata, bool $dispatch): void
    {
        if ($dispatch) {
            $event = new PreMountEvent($component, $data, $componentMetadata);
            $this->eventDispatcher->dispatch($event);
            $data = $event->getData();
        }

        foreach ($componentMetadata->getPreMounts() as $preMount) {
            if (null !== $newData = $component->$preMount($data)) {
                $data = $newData;
            }
        }
    }

    /**
     * @return array The extra metadata collected by the listeners
     */
    private function postMount(object $component, array &$data, ComponentMetadata $componentMetadata, bool $dispatch): array
    {
        $extraMetadata = [];
        if ($dispatch) {
            $event = new PostMountEvent($component, $data, $componentMetadata);
            $this->eventDispatcher->dispatch($event);
            $data = $event->getData();
            $extraMetadata = $event->getExtraMetadata();
        }

        foreach ($componentMetadata->getPostMounts() as $postMount) {
            if (null !== $newData = $component->$postMount($data)) {
                $data = $newData;
            }
        }

        return $extraMetadata;
    }

    /**
     * @return never
     */
    private function throwUnknownComponentException(string $name): void
    {
        $message = \sprintf('Unknown component "%s".', $name);
        $lowerName = strtolower($name);
        $nameLength = \strlen($lowerName);
        $alternatives = [];

        foreach (array_keys($this->config) as $type) {
            $lowerType = strtolower($type);
            $lev = levenshtein($lowerName, $lowerType);

            if ($lev <= $nameLength / 3 || str_contains($lowerType, $lowerName)) {
                $alternatives[] = $type;
            }
        }

        if ($alternatives) {
            if (1 === \count($alternatives)) {
                $message .= ' Did you mean this: "';
            } else {
                $message .= ' Did you mean one of these: "';
            }

            $message .= implode('", "', $alternatives).'"?';
        } else {
            $message .= ' And no matching anonymous component template was found.';
        }

        throw new \InvalidArgumentException($message);
    }

    public function reset(): void
    {
        $this->mountMethods = [];
        $this->writableProperties = [];
        $this->directlyWritableProperties = [];
        $this->metadata = [];
    }

    private function writeProperty(object $component, string|int $property, mixed $value): void
    {
        $iterable = is_iterable($value);
        if ($this->directlyWritableProperties[$component::class][$property][(int) $iterable] ??= $this->isDirectlyWritable($component::class, $property, $iterable)) {
            try {
                $component->$property = $value;

                return;
            } catch (\TypeError $e) {
                // For a value of the wrong type, the accessor converts DateTime objects or throws its own exception
                if (!str_starts_with($e->getMessage(), 'Cannot assign ')) {
                    throw $e;
                }
            }
        }

        $this->propertyAccessor->setValue($component, $property, $value);
    }

    /**
     * Whether writing the property directly gives the same result as the property accessor.
     *
     * The write info is asked with the context the accessor uses, so the extractor decides which mutator wins.
     * Magic methods are not allowed, so only a declared property can be found.
     * Properties with hooks and classes with __set() are excluded, because a write that fails because of the type of the value is done again by the accessor.
     */
    private function isDirectlyWritable(string $class, string|int $property, bool $iterable): bool
    {
        if (null === $this->writeInfoExtractor || \is_int($property) || method_exists($class, '__set')) {
            return false;
        }

        $writeInfo = $this->writeInfoExtractor->getWriteInfo($class, $property, [
            'enable_getter_setter_extraction' => true,
            'enable_magic_methods_extraction' => ReflectionExtractor::DISALLOW_MAGIC_METHODS,
            'enable_constructor_extraction' => false,
            'enable_adder_remover_extraction' => $iterable,
        ]);

        if (PropertyWriteInfo::TYPE_PROPERTY !== $writeInfo?->getType() || PropertyWriteInfo::VISIBILITY_PUBLIC !== $writeInfo->getVisibility() || $property !== $writeInfo->getName() || $writeInfo->isStatic()) {
            return false;
        }

        $reflectionClass = new \ReflectionClass($class);

        return $reflectionClass->hasProperty($property) && !$reflectionClass->getProperty($property)->hasHooks();
    }
}
