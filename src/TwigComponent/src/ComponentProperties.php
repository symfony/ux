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

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class ComponentProperties
{
    private const CACHE_KEY = 'ux.twig_component.component_properties';

    /**
     * @var array<class-string, array{
     *     properties: array<class-string, array{string, array{string, string, bool}, bool}>,
     *     methods: array<class-string, array{string, array{string, bool}}>,
     *  }|null>
     */
    private array $classMetadata;

    public function __construct(
        private readonly PropertyAccessorInterface $propertyAccessor,
        ?array $classMetadata = [],
        private readonly ?AdapterInterface $cache = null,
    ) {
        $cacheItem = $this->cache?->getItem(self::CACHE_KEY);

        $this->classMetadata = $cacheItem?->isHit() ? [...$cacheItem->get(), ...$classMetadata] : $classMetadata;
    }

    /**
     * @return array<string, mixed>
     */
    public function getProperties(object $component, bool $publicProps = false): array
    {
        return iterator_to_array($this->extractProperties($component, $publicProps));
    }

    public function warmup(): void
    {
        if (!$this->cache) {
            return;
        }

        foreach ($this->classMetadata as $class => $metadata) {
            if (null === $metadata) {
                $this->classMetadata[$class] = $this->loadClassMetadata($class);
            }
        }

        $this->cache->save($this->cache->getItem(self::CACHE_KEY)->set($this->classMetadata));
    }

    /**
     * @return \Generator<string, mixed>
     */
    private function extractProperties(object $component, bool $publicProps): \Generator
    {
        yield from $publicProps ? get_object_vars($component) : [];

        $metadata = $this->classMetadata[$component::class] ??= $this->loadClassMetadata($component::class);

        foreach ($metadata['properties'] as $propertyName => $property) {
            $value = $property['getter'] ? $component->{$property['getter']}() : $this->propertyAccessor->getValue($component, $propertyName);
            if ($property['destruct'] ?? false) {
                yield from $value;
            } else {
                yield $property['name'] => $value;
            }
        }

        foreach ($metadata['methods'] as $methodName => $method) {
            if ($method['destruct'] ?? false) {
                yield from $component->{$methodName}();
            } else {
                yield $method['name'] => $component->{$methodName}();
            }
        }
    }

    /**
     * @param class-string $class
     *
     * @return array{
     *   properties: array<string, array{
     *     name?: string,
     *     getter?: string,
     *     destruct?: bool
     *   }>,
     *   methods: array<string, array{
     *     name?: string,
     *     destruct?: bool
     *   }>,
     * }
     */
    private function loadClassMetadata(string $class): array
    {
        $refClass = new \ReflectionClass($class);

        $properties = [];
        // Walk the full class hierarchy so that private properties declared in
        // traits used by *parent* classes are also discovered.
        //
        // PHP's ReflectionClass::getProperties() called on a child class does
        // not return private properties from traits used in ancestor classes —
        // those are only visible when getProperties() is called on the exact
        // class that declares "use TraitName".  Iterating up via getParentClass()
        // and deduplicating by property name gives us the complete picture.
        $seenPropertyNames = [];
        $currentClass = $refClass;
        while (false !== $currentClass) {
            foreach ($currentClass->getProperties() as $property) {
                if (isset($seenPropertyNames[$property->name])) {
                    // Already processed from a more-derived class; skip.
                    continue;
                }
                $seenPropertyNames[$property->name] = true;

                if (!$attributes = $property->getAttributes(ExposeInTemplate::class)) {
                    continue;
                }
                $attribute = $attributes[0]->newInstance();
                $properties[$property->name] = [
                    'name' => $attribute->name ?? $property->name,
                    'getter' => $attribute->getter ? rtrim($attribute->getter, '()') : null,
                ];
                if ($attribute->destruct) {
                    unset($properties[$property->name]['name']);
                    $properties[$property->name]['destruct'] = true;
                }
            }
            $currentClass = $currentClass->getParentClass();
        }

        $methods = [];
        foreach ($refClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (!$attributes = $method->getAttributes(ExposeInTemplate::class)) {
                continue;
            }
            if ($method->getNumberOfRequiredParameters()) {
                throw new \LogicException(\sprintf('Cannot use "%s" on methods with required parameters (%s::%s).', ExposeInTemplate::class, $class, $method->name));
            }
            $attribute = $attributes[0]->newInstance();
            $name = $attribute->name ?? (str_starts_with($method->name, 'get') ? lcfirst(substr($method->name, 3)) : $method->name);
            $methods[$method->name] = $attribute->destruct ? ['destruct' => true] : ['name' => $name];
        }

        return [
            'properties' => $properties,
            'methods' => $methods,
        ];
    }
}
