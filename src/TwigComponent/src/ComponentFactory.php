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
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Event\PostMountEvent;
use Symfony\UX\TwigComponent\Event\PreMountEvent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class ComponentFactory
{
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
        private array $classMap,
    ) {
    }

    public function metadataFor(string $name): ComponentMetadata
    {
        $name = $this->classMap[$name] ?? $name;

        if (!$config = $this->config[$name] ?? null) {
            if (($template = $this->componentTemplateFinder->findAnonymousComponentTemplate($name)) !== null) {
                return new ComponentMetadata([
                    'key' => $name,
                    'template' => $template,
                ]);
            }

            $this->throwUnknownComponentException($name);
        }

        return new ComponentMetadata($config);
    }

    /**
     * Creates the component and "mounts" it with the passed data.
     */
    public function create(string $name, array $data = []): MountedComponent
    {
        return $this->mountFromObject(
            $this->getComponent($name),
            $data,
            $this->metadataFor($name)
        );
    }

    /**
     * @internal
     */
    public function mountFromObject(object $component, array $data, ComponentMetadata $componentMetadata): MountedComponent
    {
        $originalData = $data;
        $data = $this->preMount($component, $data, $componentMetadata);

        $this->mount($component, $data);

        // set data that wasn't set in mount on the component directly
        foreach ($data as $property => $value) {
            if ($this->propertyAccessor->isWritable($component, $property)) {
                $this->propertyAccessor->setValue($component, $property, $value);

                unset($data[$property]);
            }
        }

        $postMount = $this->postMount($component, $data, $componentMetadata);
        $data = $postMount['data'];
        $extraMetadata = $postMount['extraMetadata'];

        // create attributes from "attributes" key if exists
        $attributesVar = $componentMetadata->getAttributesVar();
        $attributes = $data[$attributesVar] ?? [];
        unset($data[$attributesVar]);

        // ensure remaining data is scalar
        foreach ($data as $key => $value) {
            if ($value instanceof \Stringable) {
                $data[$key] = (string) $value;
                continue;
            }

            $data[$key] = $value;
        }

        return new MountedComponent(
            $componentMetadata->getName(),
            $component,
            new ComponentAttributes(array_merge($attributes, $data)),
            $originalData,
            $extraMetadata,
        );
    }

    /**
     * Returns the "unmounted" component.
     */
    public function get(string $name): object
    {
        return $this->getComponent($name);
    }

    private function mount(object $component, array &$data): void
    {
        try {
            $method = (new \ReflectionClass($component))->getMethod('mount');
        } catch (\ReflectionException) {
            // no hydrate method
            return;
        }

        if ($component instanceof AnonymousComponent) {
            $component->mount($data);

            return;
        }

        $parameters = [];

        foreach ($method->getParameters() as $refParameter) {
            $name = $refParameter->getName();

            if (\array_key_exists($name, $data)) {
                $parameters[] = $data[$name];

                // remove the data element so it isn't used to set the property directly.
                unset($data[$name]);
            } elseif ($refParameter->isDefaultValueAvailable()) {
                $parameters[] = $refParameter->getDefaultValue();
            } else {
                throw new \LogicException(\sprintf('%s::mount() has a required $%s parameter. Make sure this is passed or make give a default value.', $component::class, $refParameter->getName()));
            }
        }

        $component->mount(...$parameters);
    }

    private function getComponent(string $name): object
    {
        $name = $this->classMap[$name] ?? $name;

        if (!$this->components->has($name)) {
            if ($this->isAnonymousComponent($name)) {
                return new AnonymousComponent();
            }

            $this->throwUnknownComponentException($name);
        }

        return $this->components->get($name);
    }

    private function preMount(object $component, array $data, ComponentMetadata $componentMetadata): array
    {
        $event = new PreMountEvent($component, $data, $componentMetadata);
        $this->eventDispatcher->dispatch($event);
        $data = $event->getData();

        foreach (AsTwigComponent::preMountMethods($component) as $method) {
            $newData = $component->{$method->name}($data);

            if (null !== $newData) {
                $data = $newData;
            }
        }

        return $data;
    }

    /**
     * @return array{data: array<string, mixed>, extraMetadata: array<string, mixed>}
     */
    private function postMount(object $component, array $data, ComponentMetadata $componentMetadata): array
    {
        $event = new PostMountEvent($component, $data, $componentMetadata);
        $this->eventDispatcher->dispatch($event);
        $data = $event->getData();
        $extraMetadata = $event->getExtraMetadata();

        foreach (AsTwigComponent::postMountMethods($component) as $method) {
            $newData = $component->{$method->name}($data);

            if (null !== $newData) {
                $data = $newData;
            }
        }

        return [
            'data' => $data,
            'extraMetadata' => $extraMetadata,
        ];
    }

    private function isAnonymousComponent(string $name): bool
    {
        return null !== $this->componentTemplateFinder->findAnonymousComponentTemplate($name);
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
}
