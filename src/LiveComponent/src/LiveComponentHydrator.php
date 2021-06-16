<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent;

use Doctrine\Common\Annotations\Reader;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\LiveComponent\Attribute\PreDehydrate;
use Symfony\UX\LiveComponent\Exception\UnsupportedHydrationException;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 * @internal
 */
final class LiveComponentHydrator
{
    private const CHECKSUM_KEY = '_checksum';
    private const EXPOSED_PROP_KEY = 'id';

    /** @var PropertyHydratorInterface[] */
    private $propertyHydrators;

    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /** @var Reader */
    private $annotationReader;

    /** @var string */
    private $secret;

    /**
     * @param PropertyHydratorInterface[] $propertyHydrators
     */
    public function __construct(iterable $propertyHydrators, PropertyAccessorInterface $propertyAccessor, Reader $annotationReader, string $secret)
    {
        $this->propertyHydrators = $propertyHydrators;
        $this->propertyAccessor = $propertyAccessor;
        $this->annotationReader = $annotationReader;
        $this->secret = $secret;
    }

    public function isActionAllowed(LiveComponentInterface $component, string $action): bool
    {
        foreach ((new \ReflectionClass($component))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($action === $method->name && $this->annotationReader->getMethodAnnotation($method, LiveAction::class)) {
                return true;
            }
        }

        return false;
    }

    public function dehydrate(LiveComponentInterface $component): array
    {
        foreach ($this->preDehydrateMethods($component) as $method) {
            $component->{$method->name}();
        }

        $data = [];
        $readonlyProperties = [];

        $frontendPropertyNames = [];
        foreach ($this->reflectionProperties($component) as $property) {
            $liveProp = $this->livePropFor($property);
            $name = $property->getName();
            $frontendName = $this->getFrontendFieldName($liveProp, $component, $property);

            if (isset($frontendPropertyNames[$frontendName])) {
                $message = sprintf('The field name "%s" cannot be used by multiple LiveProp properties in a component. Currently, both "%s" and "%s" are trying to use it in "%s".', $frontendName, $frontendPropertyNames[$frontendName], $name, get_class($component));

                if ($frontendName === $frontendPropertyNames[$frontendName] || $frontendName === $name) {
                    $message .= sprintf(' Try adding LiveProp(fieldName="somethingElse") for the "%s" property to avoid this.', $frontendName);
                }

                throw new \LogicException($message);
            }
            $frontendPropertyNames[$frontendName] = $name;

            if ($liveProp->isReadonly()) {
                $readonlyProperties[] = $frontendName;
            }

            // TODO: improve error message if not readable
            $value = $this->propertyAccessor->getValue($component, $name);

            $dehydratedValue = null;
            if ($method = $liveProp->dehydrateMethod()) {
                // TODO: Error checking
                $dehydratedValue = $component->$method($value);
            } else {
                $dehydratedValue = $this->dehydrateProperty($value, $name, $component);
            }

            if (count($liveProp->exposed()) > 0) {
                $data[$frontendName] = [
                    self::EXPOSED_PROP_KEY => $dehydratedValue,
                ];
                foreach ($liveProp->exposed() as $propertyPath) {
                    $value = $this->propertyAccessor->getValue($component, sprintf('%s.%s', $name, $propertyPath));
                    $data[$frontendName][$propertyPath] = $this->dehydrateProperty($value, $propertyPath, $component);
                }
            } else {
                $data[$frontendName] = $dehydratedValue;
            }
        }

        $data[self::CHECKSUM_KEY] = $this->computeChecksum($data, $readonlyProperties);

        return $data;
    }

    public function hydrate(LiveComponentInterface $component, array $data): void
    {
        $readonlyProperties = [];

        /*
         * Determine readonly properties for checksum verification. We need to do this
         * before setting properties on the component. It is unlikely but there could
         * be security implications to doing it after (component setter's could have
         * side effects).
         */
        foreach ($this->reflectionProperties($component) as $property) {
            $liveProp = $this->livePropFor($property);
            if ($liveProp->isReadonly()) {
                $readonlyProperties[] = $this->getFrontendFieldName($liveProp, $component, $property);
            }
        }

        $this->verifyChecksum($data, $readonlyProperties);

        unset($data[self::CHECKSUM_KEY]);

        foreach ($this->reflectionProperties($component) as $property) {
            $liveProp = $this->livePropFor($property);
            $name = $property->getName();
            $frontendName = $this->getFrontendFieldName($liveProp, $component, $property);

            if (!\array_key_exists($frontendName, $data)) {
                // this property was not sent
                continue;
            }

            $dehydratedValue = $data[$frontendName];
            // if there are exposed keys, then the main value should be hidden
            // in an array under self::EXPOSED_PROP_KEY. But if the value is
            // *not* an array, then use the main value. This could mean that,
            // for example, in a "post.title" situation, the "post" itself was changed.
            if (count($liveProp->exposed()) > 0 && isset($dehydratedValue[self::EXPOSED_PROP_KEY])) {
                $dehydratedValue = $dehydratedValue[self::EXPOSED_PROP_KEY];
                unset($data[$frontendName][self::EXPOSED_PROP_KEY]);
            }

            if ($method = $liveProp->hydrateMethod()) {
                // TODO: Error checking
                $value = $component->$method($dehydratedValue);
            } else {
                $value = $this->hydrateProperty($property, $dehydratedValue);
            }

            foreach ($liveProp->exposed() as $exposedProperty) {
                $propertyPath = $this->transformToArrayPath("{$name}.$exposedProperty");

                if (!$this->propertyAccessor->isReadable($data, $propertyPath)) {
                    continue;
                }

                // easy way to read off of the array
                $exposedPropertyData = $this->propertyAccessor->getValue($data, $propertyPath);

                try {
                    $this->propertyAccessor->setValue(
                        $value,
                        $exposedProperty,
                        $exposedPropertyData
                    );
                } catch (UnexpectedTypeException $e) {
                    throw new \LogicException(sprintf(
                        'Unable to set the exposed field "%s" onto the "%s" property because it has an invalid type (%s).',
                        $exposedProperty,
                        $name,
                        get_debug_type($value)
                    ), 0, $e);
                }
            }

            // TODO: improve error message if not writable
            $this->propertyAccessor->setValue($component, $name, $value);
        }

        foreach ($this->postHydrateMethods($component) as $method) {
            $component->{$method->name}();
        }
    }

    private function computeChecksum(array $data, array $readonlyProperties): string
    {
        // filter to only readonly properties
        $properties = array_filter($data, static fn($key) => \in_array($key, $readonlyProperties, true), ARRAY_FILTER_USE_KEY);

        // for read-only properties with "exposed" sub-parts,
        // only use the main value
        foreach ($properties as $key => $val) {
            if (\in_array($key, $readonlyProperties) && is_array($val)) {
                $properties[$key] = $val[self::EXPOSED_PROP_KEY];
            }
        }

        // sort so it is always consistent (frontend could have re-ordered data)
        \ksort($properties);

        return \base64_encode(\hash_hmac('sha256', http_build_query($properties), $this->secret, true));
    }

    private function verifyChecksum(array $data, array $readonlyProperties): void
    {
        if (!\array_key_exists(self::CHECKSUM_KEY, $data)) {
            throw new UnprocessableEntityHttpException('No checksum!');
        }

        if (!hash_equals($this->computeChecksum($data, $readonlyProperties), $data[self::CHECKSUM_KEY])) {
            throw new UnprocessableEntityHttpException('Invalid checksum!');
        }
    }

    /**
     * @param scalar|null|array $value
     *
     * @return mixed
     */
    private function hydrateProperty(\ReflectionProperty $property, $value)
    {
        // TODO: make compatible with PHP 7.2
        if (!$property->getType() || !$property->getType() instanceof \ReflectionNamedType || $property->getType()->isBuiltin()) {
            return $value;
        }

        foreach ($this->propertyHydrators as $hydrator) {
            try {
                return $hydrator->hydrate($property->getType()->getName(), $value);
            } catch (UnsupportedHydrationException $e) {
                continue;
            }
        }

        return $value;
    }

    /**
     * @param mixed $value
     *
     * @return scalar|null|array
     */
    private function dehydrateProperty($value, string $name, LiveComponentInterface $component)
    {
        if (\is_scalar($value) || \is_array($value) || \is_null($value)) {
            // nothing to dehydrate...
            return $value;
        }

        foreach ($this->propertyHydrators as $hydrator) {
            try {
                $value = $hydrator->dehydrate($value);

                break;
            } catch (UnsupportedHydrationException $e) {
                continue;
            }
        }

        if (!\is_scalar($value) && !\is_array($value) && !\is_null($value)) {
            throw new \LogicException(\sprintf('Cannot dehydrate property "%s" of "%s". The value "%s" does not have a dehydrator.', $name, get_class($component),  get_debug_type($value)));
        }

        return $value;
    }

    /**
     * @param \ReflectionClass|object $object
     *
     * @return \ReflectionProperty[]
     */
    private function reflectionProperties(object $object): iterable
    {
        $class = $object instanceof \ReflectionClass ? $object : new \ReflectionClass($object);

        foreach ($class->getProperties() as $property) {
            if (null !== $this->livePropFor($property)) {
                yield $property;
            }
        }

        if ($parent = $class->getParentClass()) {
            yield from $this->reflectionProperties($parent);
        }
    }

    private function livePropFor(\ReflectionProperty $property): ?LiveProp
    {
        return $this->annotationReader->getPropertyAnnotation($property, LiveProp::class);
    }

    /**
     * Transforms a path like `post.name` into `[post][name]`.
     *
     * This allows us to use the property accessor to find this
     * inside an array.
     *
     * @param string $propertyPath
     * @return string
     */
    private function transformToArrayPath(string $propertyPath): string
    {
        $parts = explode('.', $propertyPath);

        $path = '';
        foreach ($parts as $part) {
            $path .= "[{$part}]";
        }

        return $path;
    }

    /**
     * @return \ReflectionMethod[]
     */
    private function preDehydrateMethods(LiveComponentInterface $component): iterable
    {
        foreach ((new \ReflectionClass($component))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($this->annotationReader->getMethodAnnotation($method, PreDehydrate::class)) {
                yield $method;
            }
        }
    }

    /**
     * @return \ReflectionMethod[]
     */
    private function postHydrateMethods(LiveComponentInterface $component): iterable
    {
        foreach ((new \ReflectionClass($component))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($this->annotationReader->getMethodAnnotation($method, PostHydrate::class)) {
                yield $method;
            }
        }
    }

    private function getFrontendFieldName(LiveProp $liveProp, LiveComponentInterface $component, \ReflectionProperty $property): string
    {
        return $liveProp->calculateFieldName($component, $property->getName());
    }
}
