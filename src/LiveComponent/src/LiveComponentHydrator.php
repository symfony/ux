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
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadata;
use Symfony\UX\LiveComponent\Util\PropsDataHelper;
use Symfony\UX\TwigComponent\ComponentAttributes;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 *
 * @internal
 */
final class LiveComponentHydrator
{
    public const LIVE_CONTEXT = 'live-component';
    private const ATTRIBUTES_KEY = '@attributes';
    private const CHECKSUM_KEY = '@checksum';

    public function __construct(
        private NormalizerInterface|DenormalizerInterface $normalizer,
        private PropertyAccessorInterface $propertyAccessor,
        private PropertyTypeExtractorInterface $propertyTypeExtractor,
        private string $secret
    ) {
    }

    public function dehydrate(object $component, ComponentAttributes $attributes, LiveComponentMetadata $componentMetadata): array
    {
        foreach (AsLiveComponent::preDehydrateMethods($component) as $method) {
            $component->{$method->name}();
        }

        $takenFrontendPropertyNames = [];

        $flattenedProps = [];
        foreach ($componentMetadata->getAllLivePropsMetadata() as $propMetadata) {
            $propertyName = $propMetadata->getName();
            $frontendName = $propMetadata->calculateFieldName($component, $propertyName);

            if (isset($takenFrontendPropertyNames[$frontendName])) {
                $message = sprintf('The field name "%s" cannot be used by multiple LiveProp properties in a component. Currently, both "%s" and "%s" are trying to use it in "%s".', $frontendName, $takenFrontendPropertyNames[$frontendName], $propertyName, \get_class($component));

                if ($frontendName === $takenFrontendPropertyNames[$frontendName] || $frontendName === $propertyName) {
                    $message .= sprintf(' Try adding LiveProp(fieldName="somethingElse") for the "%s" property to avoid this.', $frontendName);
                }

                throw new \LogicException($message);
            }

            $takenFrontendPropertyNames[$frontendName] = $propertyName;

            try {
                $rawPropertyValue = $this->propertyAccessor->getValue($component, $propertyName);
            } catch (UninitializedPropertyException $exception) {
                throw new \LogicException(sprintf('The "%s" property on the "%s" component is uninitialized. Did you forget to pass this into the component?', $propertyName, \get_class($component)), 0, $exception);
            }

            $dehydratedValue = $rawPropertyValue;

            if ($method = $propMetadata->dehydrateMethod()) {
                if (!method_exists($component, $method)) {
                    throw new \LogicException(sprintf('The "%s" component has a dehydrateMethod of "%s" but the method does not exist.', \get_class($component), $method));
                }
                $dehydratedValue = $component->$method($dehydratedValue);
            } elseif (\is_object($rawPropertyValue)) {
                $dehydratedValue = $this->normalizer->normalize(
                    $dehydratedValue,
                    'json',
                    [self::LIVE_CONTEXT => true]
                );
            }

            if ($propMetadata->isIdentityWritable()) {
                $this->preventArrayDehydratedValueForObjectThatIsWritable($dehydratedValue, $propMetadata->getType(), $propMetadata->isBuiltIn(), $propMetadata->getName(), false);
            }
            $flattenedProps[$frontendName] = $dehydratedValue;

            foreach ($propMetadata->writablePaths() as $path) {
                try {
                    $pathValue = $this->propertyAccessor->getValue(
                        $rawPropertyValue,
                        $this->adjustPropertyPathForData($rawPropertyValue, $path)
                    );
                } catch (NoSuchPropertyException $e) {
                    throw new \LogicException(sprintf('The writable path "%s" does not exist on the "%s" property of the "%s" component.', $path, $propertyName, \get_class($component)), 0, $e);
                }

                if (\is_object($pathValue)) {
                    $originalPathValueClass = \get_class($pathValue);
                    $pathValue = $this->normalizer->normalize(
                        $pathValue,
                        'json',
                        [self::LIVE_CONTEXT => true]
                    );

                    $this->preventArrayDehydratedValueForObjectThatIsWritable($pathValue, $originalPathValueClass, false, sprintf('%s.%s', $propMetadata->getName(), $path), false);
                }

                $flattenedProps[sprintf('%s.%s', $frontendName, $path)] = $pathValue;
            }
        }

        if (0 !== \count($attributes->all())) {
            $flattenedProps[self::ATTRIBUTES_KEY] = $attributes->all();
        }
        $flattenedProps[self::CHECKSUM_KEY] = $this->calculateChecksum($flattenedProps, $componentMetadata);

        return PropsDataHelper::expandToFrontendArray($flattenedProps);
    }

    public function hydrate(object $component, array $props, LiveComponentMetadata $componentMetadata): ComponentAttributes
    {
        // transform into a flat array of property paths
        $props = PropsDataHelper::flattenToBackendArray($props);
        if (!$this->isChecksumValid($props, $componentMetadata)) {
            throw new UnprocessableEntityHttpException('Invalid or missing checksum. This usually means that you tried to change a property that is not writable: true.');
        }
        unset($props[self::CHECKSUM_KEY]);

        $attributes = new ComponentAttributes($props[self::ATTRIBUTES_KEY] ?? []);
        unset($props[self::ATTRIBUTES_KEY]);

        foreach ($componentMetadata->getAllLivePropsMetadata() as $propMetadata) {
            $propertyName = $propMetadata->getName();
            $frontendName = $propMetadata->calculateFieldName($component, $propertyName);

            if (!\array_key_exists($frontendName, $props)) {
                // this property was not sent, so skip
                // even if this has writable paths, if no identity is sent,
                // then there's nothing to write those onto
                continue;
            }

            $value = $props[$frontendName];
            if ($propMetadata->hydrateMethod()) {
                if (!method_exists($component, $propMetadata->hydrateMethod())) {
                    throw new \LogicException(sprintf('The "%s" component has a hydrateMethod of "%s" but the method does not exist.', \get_class($component), $propMetadata->hydrateMethod()));
                }
                $value = $component->{$propMetadata->hydrateMethod()}($value);
            } else {
                $value = $this->hydrateValue(
                    $value,
                    $propMetadata->getType(),
                    $propMetadata->allowsNull(),
                    $propMetadata->isBuiltIn(),
                    $propertyName,
                    $propMetadata->isIdentityWritable(),
                    \get_class($component),
                );
            }

            // if the value is an object or an array, then we need to hydrate writable paths
            if (\is_array($value) || \is_object($value)) {
                $value = $this->hydrateAndSetWritablePaths(
                    $propMetadata->writablePaths(),
                    $frontendName,
                    $value,
                    $props,
                    \get_class($component)
                );
            }

            // TODO: what if the new value is NOT settable onto the property?
            // for example, a "string" value is being set into an "int". Or
            // null is being set into a non-nullable property? We need to fail
            // hard in a way that (A) is easy to debug and (B) triggers a
            // 400 level status code.

            // finally set the value onto the component property
            // TODO: improve error message if not writable
            $this->propertyAccessor->setValue($component, $propertyName, $value);
        }

        foreach (AsLiveComponent::postHydrateMethods($component) as $method) {
            $component->{$method->name}();
        }

        return $attributes;
    }

    public static function getInternalPropNames(): array
    {
        return [self::ATTRIBUTES_KEY, self::CHECKSUM_KEY];
    }

    private static function coerceScalarValue(string $value, string $type, bool $allowsNull): int|float|bool|null
    {
        $value = trim($value);

        if ('' === $value && $allowsNull) {
            return null;
        }

        return match ($type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => (bool) $value,
            default => throw new \LogicException(sprintf('Cannot coerce value "%s" to type "%s"', $value, $type)),
        };
    }

    /**
     * @param array $flattenedDehydratedPropsData In the form returned by PropsDataHelper::flattenToBackendArray()
     */
    private function calculateChecksum(array $flattenedDehydratedPropsData, LiveComponentMetadata $componentMetadata): ?string
    {
        $readonlyPaths = $componentMetadata->getReadonlyPropPaths();
        $readonlyPaths[] = self::ATTRIBUTES_KEY;

        // get the data for only the readonly props
        $readonlyPropsData = array_filter($flattenedDehydratedPropsData, function ($key) use ($readonlyPaths) {
            return \in_array($key, $readonlyPaths, true);
        }, \ARRAY_FILTER_USE_KEY);

        // sort so it is always consistent (frontend could have re-ordered data)
        ksort($readonlyPropsData);

        return base64_encode(hash_hmac('sha256', var_export($readonlyPropsData, true), $this->secret, true));
    }

    private function isChecksumValid(array $flattenedProps, LiveComponentMetadata $componentMetadata): bool
    {
        $expectedChecksum = $this->calculateChecksum($flattenedProps, $componentMetadata);

        if (!\array_key_exists(self::CHECKSUM_KEY, $flattenedProps)) {
            return false;
        }

        $sentChecksum = $flattenedProps[self::CHECKSUM_KEY];

        return hash_equals($expectedChecksum, $sentChecksum);
    }

    /**
     * @param string[] $writablePaths
     */
    private function hydrateAndSetWritablePaths(array $writablePaths, string $frontendName, array|object $propertyValue, array $props, string $componentClass): array|object
    {
        foreach ($writablePaths as $writablePath) {
            $propertyPath = sprintf('%s.%s', $frontendName, $writablePath);
            if (!\array_key_exists($propertyPath, $props)) {
                // handle case where the writable path value is an array
                $embeddedValues = $this->extractEmbeddedValuesFromFlattenedArray($props, $propertyPath);

                if (0 === \count($embeddedValues)) {
                    // this writable path property was not sent, so skip
                    continue;
                }

                $props[$propertyPath] = $embeddedValues;
            }

            // easy way to read off of the array
            $writablePathData = $props[$propertyPath];

            // smarter hydration currently only supported for top-level writable
            // e.g. writablePaths: ['post.createdAt'] is not supported.
            if (0 === substr_count($writablePath, '.') && \is_object($propertyValue)) {
                /*
                 * Imagine the property is a Product object with writable ['createdAt'].
                 * The frontend will send a "createdAt" key with a string value
                 * that we need to "denormalize" into the DateTime object. An easy
                 * way to do this is to denormalize the entire Product object, but
                 * only set the "createdAt" property. This offloads the work of
                 * reading the type of the property to the denormalizer.
                 */

                $types = $this->propertyTypeExtractor->getTypes(\get_class($propertyValue), $writablePath);
                $type = null === $types ? null : $types[0] ?? null;

                $writablePathData = $this->hydrateValue(
                    $writablePathData,
                    $type ? $type->getClassName() : null,
                    $type ? $type->isNullable() : true,
                    $type ? Type::BUILTIN_TYPE_OBJECT !== $type->getBuiltinType() : false,
                    $propertyPath,
                    true,
                    $componentClass,
                );
            }

            try {
                // TODO: handle bad types being passed here, one exception is
                // Symfony\Component\PropertyAccess\Exception\InvalidArgumentException
                // like if (de)normalization gets weird and we try to set a string
                // onto a DateTime property.
                $this->propertyAccessor->setValue(
                    $propertyValue,
                    $this->adjustPropertyPathForData($propertyValue, $writablePath),
                    $writablePathData
                );
            } catch (UnexpectedTypeException $e) {
                throw new \LogicException(sprintf('Unable to set the writable path "%s" onto the "%s" property because it has an invalid type (%s).', $writablePath, $frontendName, get_debug_type($propertyValue)), 0, $e);
            }
        }

        return $propertyValue;
    }

    /**
     * We do NOT allow for "writable objects" that dehydrate to an array.
     *
     * This is a pragmatic decision. But also, if a value is writable, it is
     * meant to be modified by the user via, for example, form fields. It
     * doesn't make sense for the value of a field to be {name: "foo", price: 10},
     * as the user can't modify that entire object at once.
     */
    private function preventArrayDehydratedValueForObjectThatIsWritable(mixed $dehydratedValue, ?string $type, bool $isBuiltIn, string $pathName, bool $isHydrating): void
    {
        // if this is a scalar value, then it's fine
        if (null === $dehydratedValue || \is_scalar($dehydratedValue)) {
            return;
        }

        // if this is a non-class type (e.g. scalar, array), then it's fine
        if (!$type || $isBuiltIn) {
            return;
        }

        // different errors based on hydrating vs dehydrating
        if ($isHydrating) {
            // this should be user error - but a user that is trying bad stuff
            $message = sprintf('The model path "%s" was sent as an array, but this could not be hydrated to an object as that is not allowed.', $pathName);
        } else {
            // this should be developer error, not user error
            $message = sprintf('The LiveProp path "%s" is an object that was dehydrated to an array *and* it is writable. That\'s not allowed.', $pathName);
            if (0 === substr_count($pathName, '.')) {
                $message .= ' You probably want to set writable to only the properties on your class that should be writable (e.g. writable: [\'name\', \'price\']).';
            }
        }

        throw new UnprocessableEntityHttpException($message);
    }

    private function adjustPropertyPathForData(mixed $rawPropertyValue, string $propertyPath): string
    {
        $parts = explode('.', $propertyPath);
        $currentValue = $rawPropertyValue;
        $finalPropertyPath = '';
        foreach ($parts as $part) {
            if (\is_array($currentValue)) {
                $finalPropertyPath .= sprintf('[%s]', $part);

                continue;
            }

            if ('' !== $finalPropertyPath) {
                $finalPropertyPath .= '.';
            }

            $finalPropertyPath .= $part;
        }

        return $finalPropertyPath;
    }

    /**
     * Imagine the following props:.
     *
     *    * "stuff.details.key1" => "changed key1"
     *    * "stuff.details.new_key" => "new value"
     *
     * And we want to set the "stuff.details" writable path. This method
     * will extract the "stuff.details" values from the flattened array
     * and return them in a new array:
     *
     *   * ["key1" => "changed key1", "new_key" => "new value"]
     */
    private function extractEmbeddedValuesFromFlattenedArray(array $props, string $propertyPath): array
    {
        $embeddedValues = [];
        foreach ($props as $propName => $propValue) {
            if (str_starts_with($propName, sprintf('%s.', $propertyPath))) {
                $shortenedKey = substr($propName, \strlen($propertyPath) + 1);
                $embeddedValues[$shortenedKey] = $propValue;
                unset($props[$propName]);
            }
        }

        return $embeddedValues;
    }

    private function hydrateValue(mixed $value, ?string $type, bool $allowsNull, bool $isBuiltIn, string $pathName, bool $isWritable, string $componentClass): mixed
    {
        if (\is_string($value) && \in_array($type, ['int', 'float', 'bool'], true)) {
            return self::coerceScalarValue($value, $type, $allowsNull);
        }

        if ($type && $allowsNull && is_a($type, \BackedEnum::class, true) && !\in_array($value, array_map(fn (\BackedEnum $e) => $e->value, $type::cases()))) {
            return null;
        }

        if (null === $value || null === $type || $isBuiltIn) {
            return $value;
        }

        if ($isWritable) {
            $this->preventArrayDehydratedValueForObjectThatIsWritable($value, $type, $isBuiltIn, $pathName, true);
        }

        try {
            return $this->normalizer->denormalize(
                $value,
                $type,
                'json',
                [self::LIVE_CONTEXT => true]
            );
        } catch (ExceptionInterface $exception) {
            $json = json_encode($value);
            $message = sprintf(
                'The normalizer was used to hydrate/denormalize the "%s" property on your "%s" live component, but it failed: %s',
                $pathName,
                $componentClass,
                $exception->getMessage()
            );

            // unless the data is gigantic, include it in the error to help
            if (\strlen($json) < 1000) {
                $message .= sprintf(' The data sent from the frontend was: %s', $json);
            }

            throw new \LogicException($message, 0, $exception);
        }
    }
}
