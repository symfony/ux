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

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\UX\TwigComponent\Event\PostMountEvent;
use Symfony\UX\TwigComponent\Event\PreMountEvent;
use Twig\Environment;
use Twig\Runtime\EscaperRuntime;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class ComponentFactory implements ResetInterface
{
    private array $mountMethods = [];

    /**
     * @param array<string, array>        $config
     * @param array<class-string, string> $classMap
     */
    public function __construct(
        private ComponentTemplateFinderInterface $componentTemplateFinder,
        private ServiceLocator $components,
        private PropertyAccessorInterface $propertyAccessor,
        private EventDispatcherInterface $eventDispatcher,
        private array $config,
        private readonly array $classMap,
        private readonly Environment $twig,
    ) {
    }

    public function metadataFor(string $name): ComponentMetadata
    {
        if ($config = $this->config[$name] ?? null) {
            return new ComponentMetadata($config);
        }

        if ($template = $this->componentTemplateFinder->findAnonymousComponentTemplate($name)) {
            $this->config[$name] = [
                'key' => $name,
                'template' => $template,
            ];

            return new ComponentMetadata($this->config[$name]);
        }

        if ($mappedName = $this->classMap[$name] ?? null) {
            if ($config = $this->config[$mappedName] ?? null) {
                return new ComponentMetadata($config);
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
        $event = $this->preMount($component, $data, $componentMetadata);
        $data = $event->getData();

        $this->mount($component, $data, $componentMetadata);

        if (!$componentMetadata->isAnonymous()) {
            // set data that wasn't set in mount on the component directly
            foreach ($data as $property => $value) {
                if ($this->propertyAccessor->isWritable($component, $property)) {
                    $this->propertyAccessor->setValue($component, $property, $value);
                    unset($data[$property]);
                }
            }
        }

        $postMount = $this->postMount($component, $data, $componentMetadata);
        $data = $postMount->getData();

        // create attributes from "attributes" key if exists
        $attributesVar = $componentMetadata->getAttributesVar();
        $attributes = $data[$attributesVar] ?? [];
        unset($data[$attributesVar]);

        foreach ($data as $key => $value) {
            if ($value instanceof \Stringable) {
                $data[$key] = (string) $value;
            }
        }

        return new MountedComponent(
            $componentMetadata->getName(),
            $component,
            new ComponentAttributes([...$attributes, ...$data], $this->twig->getRuntime(EscaperRuntime::class)),
            $originalData,
            $postMount->getExtraMetadata(),
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

    private function mount(object $component, array &$data, ComponentMetadata $componentMetadata): void
    {
        if ($component instanceof AnonymousComponent) {
            $component->mount($data);

            return;
        }

        if (!$componentMetadata->getMounts()) {
            return;
        }

        $mount = $this->mountMethods[$component::class] ??= (new \ReflectionClass($component))->getMethod('mount');

        $parameters = [];
        foreach ($mount->getParameters() as $refParameter) {
            if (\array_key_exists($name = $refParameter->getName(), $data)) {
                $parameters[] = $data[$name];
                // remove the data element so it isn't used to set the property directly.
                unset($data[$name]);
            } elseif ($refParameter->isDefaultValueAvailable()) {
                $parameters[] = $refParameter->getDefaultValue();
            } else {
                throw new \LogicException(\sprintf('%s has a required $%s parameter. Make sure to pass it or give it a default value.', $component::class.'::mount()', $name));
            }
        }

        $mount->invoke($component, ...$parameters);
    }

    private function preMount(object $component, array $data, ComponentMetadata $componentMetadata): PreMountEvent
    {
        $event = new PreMountEvent($component, $data, $componentMetadata);
        $this->eventDispatcher->dispatch($event);

        $data = $event->getData();
        foreach ($componentMetadata->getPreMounts() as $preMount) {
            if (null !== $newData = $component->$preMount($data)) {
                $event->setData($data = $newData);
            }
        }

        return $event;
    }

    private function postMount(object $component, array $data, ComponentMetadata $componentMetadata): PostMountEvent
    {
        $event = new PostMountEvent($component, $data, $componentMetadata);
        $this->eventDispatcher->dispatch($event);

        $data = $event->getData();
        foreach ($componentMetadata->getPostMounts() as $postMount) {
            if (null !== $newData = $component->$postMount($data)) {
                $event->setData($data = $newData);
            }
        }

        return $event;
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
    }
}
