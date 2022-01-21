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

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LivePropContext;
use Symfony\UX\LiveComponent\Exception\UnsupportedHydrationException;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 *
 * @internal
 */
final class LiveComponentHydrator
{
    private const CHECKSUM_KEY = '_checksum';
    private const EXPOSED_PROP_KEY = '_id';

    /** @var PropertyHydratorInterface[] */
    private iterable $propertyHydrators;
    private PropertyAccessorInterface $propertyAccessor;
    private string $secret;

    /**
     * @param PropertyHydratorInterface[] $propertyHydrators
     */
    public function __construct(iterable $propertyHydrators, PropertyAccessorInterface $propertyAccessor, string $secret)
    {
        $this->propertyHydrators = $propertyHydrators;
        $this->propertyAccessor = $propertyAccessor;
        $this->secret = $secret;
    }

    public function dehydrate(object $component): array
    {
        foreach (AsLiveComponent::preDehydrateMethods($component) as $method) {
            $component->{$method->name}();
        }

        $data = [];
        $readonlyProperties = [];
        $frontendPropertyNames = [];

        foreach (AsLiveComponent::liveProps($component) as $context) {
            $property = $context->reflectionProperty();
            $liveProp = $context->liveProp();
            $name = $property->getName();
            $frontendName = $liveProp->calculateFieldName($component, $property->getName());

            if (isset($frontendPropertyNames[$frontendName])) {
                $message = sprintf('The field name "%s" cannot be used by multiple LiveProp properties in a component. Currently, both "%s" and "%s" are trying to use it in "%s".', $frontendName, $frontendPropertyNames[$frontendName], $name, \get_class($component));

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

            if (\count($liveProp->exposed()) > 0) {
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

    public function hydrate(object $component, array $data): void
    {
        $readonlyProperties = [];

        /** @var LivePropContext[] $propertyContexts */
        $propertyContexts = iterator_to_array(AsLiveComponent::liveProps($component));

        /*
         * Determine readonly properties for checksum verification. We need to do this
         * before setting properties on the component. It is unlikely but there could
         * be security implications to doing it after (component setter's could have
         * side effects).
         */
        foreach ($propertyContexts as $context) {
            $liveProp = $context->liveProp();
            $name = $context->reflectionProperty()->getName();

            if ($liveProp->isReadonly()) {
                $readonlyProperties[] = $liveProp->calculateFieldName($component, $name);
            }
        }

        $this->verifyChecksum($data, $readonlyProperties);

        unset($data[self::CHECKSUM_KEY]);

        foreach ($propertyContexts as $context) {
            $property = $context->reflectionProperty();
            $liveProp = $context->liveProp();
            $name = $property->getName();
            $frontendName = $liveProp->calculateFieldName($component, $name);

            if (!\array_key_exists($frontendName, $data)) {
                // this property was not sent
                continue;
            }

            $dehydratedValue = $data[$frontendName];
            // if there are exposed keys, then the main value should be hidden
            // in an array under self::EXPOSED_PROP_KEY. But if the value is
            // *not* an array, then use the main value. This could mean that,
            // for example, in a "post.title" situation, the "post" itself was changed.
            if (\count($liveProp->exposed()) > 0 && isset($dehydratedValue[self::EXPOSED_PROP_KEY])) {
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
                    throw new \LogicException(sprintf('Unable to set the exposed field "%s" onto the "%s" property because it has an invalid type (%s).', $exposedProperty, $name, get_debug_type($value)), 0, $e);
                }
            }

            // TODO: improve error message if not writable
            $this->propertyAccessor->setValue($component, $name, $value);
        }

        foreach (AsLiveComponent::postHydrateMethods($component) as $method) {
            $component->{$method->name}();
        }
    }

    private function computeChecksum(array $data, array $readonlyProperties): string
    {
        // filter to only readonly properties
        $properties = array_filter($data, static fn ($key) => \in_array($key, $readonlyProperties, true), \ARRAY_FILTER_USE_KEY);

        // for read-only properties with "exposed" sub-parts,
        // only use the main value
        foreach ($properties as $key => $val) {
            if (\in_array($key, $readonlyProperties) && \is_array($val) && isset($val[self::EXPOSED_PROP_KEY])) {
                $properties[$key] = $val[self::EXPOSED_PROP_KEY];
            }
        }

        // sort so it is always consistent (frontend could have re-ordered data)
        ksort($properties);

        return base64_encode(hash_hmac('sha256', http_build_query($properties), $this->secret, true));
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
     * @param scalar|array|null $value
     *
     * @return mixed
     */
    private function hydrateProperty(\ReflectionProperty $property, $value)
    {
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
     * @return scalar|array|null
     */
    private function dehydrateProperty($value, string $name, object $component)
    {
        if (is_scalar($value) || \is_array($value) || null === $value) {
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

        if (!is_scalar($value) && !\is_array($value) && null !== $value) {
            throw new \LogicException(sprintf('Cannot dehydrate property "%s" of "%s". The value "%s" does not have a dehydrator.', $name, \get_class($component), get_debug_type($value)));
        }

        return $value;
    }

    /**
     * Transforms a path like `post.name` into `[post][name]`.
     *
     * This allows us to use the property accessor to find this
     * inside an array.
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
}
