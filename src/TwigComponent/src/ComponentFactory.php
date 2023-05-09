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
     * @param array<string, array> $config
     */
    public function __construct(
        private ServiceLocator $components,
        private PropertyAccessorInterface $propertyAccessor,
        private EventDispatcherInterface $eventDispatcher,
        private array $config
    ) {
    }

    public function metadataFor(string $name): ComponentMetadata
    {
        if (!$config = $this->config[$name] ?? null) {
            throw new \InvalidArgumentException(sprintf('Unknown component "%s". The registered components are: %s', $name, implode(', ', array_keys($this->config))));
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
        $data = $this->preMount($component, $data);

        $this->mount($component, $data);

        // set data that wasn't set in mount on the component directly
        foreach ($data as $property => $value) {
            if ($this->propertyAccessor->isWritable($component, $property)) {
                $this->propertyAccessor->setValue($component, $property, $value);

                unset($data[$property]);
            }
        }

        $data = $this->postMount($component, $data);

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

            if (!\is_scalar($value) && null !== $value) {
                throw new \LogicException(sprintf('A "%s" prop was passed when creating the "%s" component. No matching %s property or mount() argument was found, so we attempted to use this as an HTML attribute. But, the value is not a scalar (it\'s a %s). Did you mean to pass this to your component or is there a typo on its name?', $key, $componentMetadata->getName(), $key, get_debug_type($value)));
            }
        }

        return new MountedComponent(
            $componentMetadata->getName(),
            $component,
            new ComponentAttributes(array_merge($attributes, $data)),
            $originalData
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
                throw new \LogicException(sprintf('%s::mount() has a required $%s parameter. Make sure this is passed or make give a default value.', \get_class($component), $refParameter->getName()));
            }
        }

        $component->mount(...$parameters);
    }

    private function getComponent(string $name): object
    {
        if (!$this->components->has($name)) {
            throw new \InvalidArgumentException(sprintf('Unknown component "%s". The registered components are: %s', $name, implode(', ', array_keys($this->components->getProvidedServices()))));
        }

        return $this->components->get($name);
    }

    private function preMount(object $component, array $data): array
    {
        $event = new PreMountEvent($component, $data);
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

    private function postMount(object $component, array $data): array
    {
        $event = new PostMountEvent($component, $data);
        $this->eventDispatcher->dispatch($event);
        $data = $event->getData();

        foreach (AsTwigComponent::postMountMethods($component) as $method) {
            $newData = $component->{$method->name}($data);

            if (null !== $newData) {
                $data = $newData;
            }
        }

        return $data;
    }
}
