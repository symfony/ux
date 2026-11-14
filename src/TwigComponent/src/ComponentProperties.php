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
use Symfony\Component\PropertyAccess\PropertyAccessor;
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

    /**
     * Whether exposed properties can be read without the property accessor.
     *
     * A decorated or replaced accessor must keep receiving every read.
     */
    private readonly bool $directReads;

    public function __construct(
        private readonly PropertyAccessorInterface $propertyAccessor,
        ?array $classMetadata = [],
        private readonly ?AdapterInterface $cache = null,
    ) {
        $cacheItem = $this->cache?->getItem(self::CACHE_KEY);

        $this->classMetadata = $cacheItem?->isHit() ? $cacheItem->get() + $classMetadata : $classMetadata;
        $this->directReads = PropertyAccessor::class === $propertyAccessor::class;
    }

    /**
     * @return array<string, mixed>
     */
    public function getProperties(object $component, bool $publicProps = false): array
    {
        $metadata = $this->classMetadata[$component::class] ??= $this->loadClassMetadata($component::class);

        $properties = $publicProps ? get_object_vars($component) : [];

        foreach ($metadata['properties'] as $propertyName => $property) {
            if ($property['getter']) {
                $value = $component->{$property['getter']}();
            } elseif (($property['direct'] ?? false) && isset($component->$propertyName)) {
                // isset() is false for null and for an uninitialized property: both are left to the accessor, which reports them as it always did
                $value = $component->$propertyName;
            } else {
                $value = $this->propertyAccessor->getValue($component, $propertyName);
            }

            if ($property['destruct'] ?? false) {
                $properties = [...$properties, ...$value];
            } else {
                $properties[$property['name']] = $value;
            }
        }

        foreach ($metadata['methods'] as $methodName => $method) {
            if ($method['destruct'] ?? false) {
                $properties = [...$properties, ...$component->{$methodName}()];
            } else {
                $properties[$method['name']] = $component->{$methodName}();
            }
        }

        return $properties;
    }

    public function warmup(): void
    {
        if (!$this->cache) {
            return;
        }

        foreach ($this->classMetadata as $class => $metadata) {
            $this->classMetadata[$class] = $this->loadClassMetadata($class);
        }

        $this->cache->save($this->cache->getItem(self::CACHE_KEY)->set($this->classMetadata));
    }

    /**
     * @param class-string $class
     *
     * @return array{
     *   properties: array<string, array{
     *     name?: string,
     *     getter?: string,
     *     direct?: bool,
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
                if (!$attribute->getter && $this->isDirectlyReadable($refClass, $property)) {
                    $properties[$property->name]['direct'] = true;
                }
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

    /**
     * Whether reading the property directly gives the same value as the property accessor.
     *
     * The accessor looks for an accessor method before falling back to the property, so a class that declares one must keep going through it.
     */
    private function isDirectlyReadable(\ReflectionClass $class, \ReflectionProperty $property): bool
    {
        if (!$this->directReads || !$property->isPublic() || $property->isStatic() || $property->hasHooks()) {
            return false;
        }

        $camelized = '' === ltrim($property->name, '_') ? $property->name : str_replace(' ', '', ucwords(str_replace('_', ' ', $property->name)));

        foreach (['get'.$camelized, lcfirst($camelized), 'is'.$camelized, 'has'.$camelized, 'can'.$camelized] as $method) {
            if ($class->hasMethod($method)) {
                return false;
            }
        }

        return true;
    }
}
