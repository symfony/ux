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

use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 */
final class ComponentFactory
{
    private ServiceLocator $components;
    private PropertyAccessorInterface $propertyAccessor;

    /** @var array<string, array> */
    private array $config;

    public function __construct(ServiceLocator $components, PropertyAccessorInterface $propertyAccessor, array $config)
    {
        $this->components = $components;
        $this->propertyAccessor = $propertyAccessor;
        $this->config = $config;
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
        $component = $this->getComponent($name);
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
        $attributes = $data['attributes'] ?? [];
        unset($data['attributes']);

        // ensure remaining data is scalar
        foreach ($data as $key => $value) {
            if (!is_scalar($value) && null !== $value) {
                throw new \LogicException(sprintf('Unable to use "%s" (%s) as an attribute. Attributes must be scalar or null. If you meant to mount this value on "%s", make sure "$%1$s" is a writable property or create a mount() method with a "$%1$s" argument.', $key, get_debug_type($value), $component::class));
            }
        }

        return new MountedComponent($name, $component, new ComponentAttributes(array_merge($attributes, $data)));
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
        } catch (\ReflectionException $e) {
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
        foreach (AsTwigComponent::preMountMethods($component) as $method) {
            $data = $component->{$method->name}($data);
        }

        return $data;
    }

    private function postMount(object $component, array $data): array
    {
        foreach (AsTwigComponent::postMountMethods($component) as $method) {
            $data = $component->{$method->name}($data);
        }

        return $data;
    }
}
