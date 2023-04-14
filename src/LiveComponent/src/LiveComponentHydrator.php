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

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PropertyAccess\Exception\ExceptionInterface as PropertyAccessExceptionInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Exception\HydrationException;
use Symfony\UX\LiveComponent\Hydration\HydrationExtensionInterface;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadata;
use Symfony\UX\LiveComponent\Metadata\LivePropMetadata;
use Symfony\UX\LiveComponent\Util\DehydratedProps;
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

    /**
     * @param iterable<HydrationExtensionInterface> $hydrationExtensions
     */
    public function __construct(
        private iterable $hydrationExtensions,
        private PropertyAccessorInterface $propertyAccessor,
        private NormalizerInterface|DenormalizerInterface $normalizer,
        private string $secret
    ) {
    }

    public function dehydrate(object $component, ComponentAttributes $attributes, LiveComponentMetadata $componentMetadata): DehydratedProps
    {
        foreach (AsLiveComponent::preDehydrateMethods($component) as $method) {
            $component->{$method->name}();
        }

        $takenFrontendPropertyNames = [];

        $dehydratedProps = new DehydratedProps();
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

            // 1) Dehydrate main value
            try {
                $rawPropertyValue = $this->propertyAccessor->getValue($component, $propertyName);
            } catch (UninitializedPropertyException $exception) {
                throw new \LogicException(sprintf('The "%s" property on the "%s" component is uninitialized. Did you forget to pass this into the component?', $propertyName, \get_class($component)), 0, $exception);
            }

            $dehydratedValue = $this->dehydrateValue($rawPropertyValue, $propMetadata, $component);

            $dehydratedProps->addPropValue($frontendName, $dehydratedValue);

            // 2) Fetch writable paths
            if (\is_object($rawPropertyValue) || \is_array($rawPropertyValue)) {
                foreach ($propMetadata->writablePaths() as $path) {
                    try {
                        $pathValue = $this->propertyAccessor->getValue(
                            $rawPropertyValue,
                            $this->adjustPropertyPathForData($rawPropertyValue, $path)
                        );
                    } catch (NoSuchPropertyException $e) {
                        throw new \LogicException(sprintf('The writable path "%s" does not exist on the "%s" property of the "%s" component.', $path, $propertyName, \get_class($component)), 0, $e);
                    } catch (PropertyAccessExceptionInterface $e) {
                        throw new \LogicException(sprintf('The writable path "%s" on the "%s" property of the "%s" component could not be read: %s', $path, $propertyName, \get_class($component), $e->getMessage()), 0, $e);
                    }

                    // TODO: maybe we allow support the same types as LiveProps later
                    if (!$this->isValueValidDehydratedValue($pathValue)) {
                        throw new \LogicException(sprintf('The writable path "%s" on the "%s" property of the "%s" component must be a scalar or array of scalars.', $path, $propertyName, \get_class($component)));
                    }

                    $dehydratedProps->addNestedProp($frontendName, $path, $pathValue);
                }
            }
        }

        if (0 !== \count($attributes->all())) {
            $dehydratedProps->addPropValue(self::ATTRIBUTES_KEY, $attributes->all());
        }

        $checksum = $this->calculateChecksum($dehydratedProps->getProps());
        $dehydratedProps->addPropValue(self::CHECKSUM_KEY, $checksum);

        return $dehydratedProps;
    }

    /**
     * Takes the read-only props and updatedProps and hydrates the component.
     *
     * $props:  The original props - i.e. the return value from dehydrate():
     *
     *     ['name' => 'ryan', 'food' => 5]
     *
     * $updatedProps A one-dimensional array of any props that should change:
     *
     *     ['name' => 'kevin', 'food.name' => 'Pasta']
     */
    public function hydrate(object $component, array $props, array $updatedProps, LiveComponentMetadata $componentMetadata, array $updatedPropsFromParent = []): ComponentAttributes
    {
        $dehydratedOriginalProps = $this->combineAndValidateProps($props, $updatedPropsFromParent);
        $dehydratedUpdatedProps = DehydratedProps::createFromUpdatedArray($updatedProps);

        $attributes = new ComponentAttributes($dehydratedOriginalProps->getPropValue(self::ATTRIBUTES_KEY, []));
        $dehydratedOriginalProps->removePropValue(self::ATTRIBUTES_KEY);

        foreach ($componentMetadata->getAllLivePropsMetadata() as $propMetadata) {
            $frontendName = $propMetadata->calculateFieldName($component, $propMetadata->getName());
            if (!$dehydratedOriginalProps->hasPropValue($frontendName)) {
                // this property was not sent, so skip
                // even if this has writable paths, if no identity is sent,
                // then there's nothing to write those onto
                continue;
            }

            /*
            | 1) Hydrate and set ORIGINAL data for this LiveProp.
            */
            $propertyValue = $this->hydrateValue(
                $dehydratedOriginalProps->getPropValue($frontendName),
                $propMetadata,
                $component,
            );

            /*
             | 2) Set ORIGINAL "writable paths" data for this LiveProp.
             | (which may represent data that was updated on a previous request)
             */
            $originalWritablePaths = $this->calculateWritablePaths($propMetadata, $propertyValue, $dehydratedOriginalProps, $frontendName, \get_class($component));
            $propertyValue = $this->setWritablePaths(
                $originalWritablePaths,
                $frontendName,
                $propertyValue,
                $dehydratedOriginalProps,
            );

            // set this value now, in case the user sends bad data later
            $this->propertyAccessor->setValue($component, $propMetadata->getName(), $propertyValue);

            /*
             | 3) Hydrate and set UPDATED data for this LiveProp if one was sent.
             */
            if ($dehydratedUpdatedProps->hasPropValue($frontendName)) {
                if (!$propMetadata->isIdentityWritable()) {
                    throw new HydrationException(sprintf('The model "%s" was sent for update, but it is not writable. Try adding "writable: true" to the $%s property in %s.', $frontendName, $propMetadata->getName(), \get_class($component)));
                }
                try {
                    $propertyValue = $this->hydrateValue(
                        $dehydratedUpdatedProps->getPropValue($frontendName),
                        $propMetadata,
                        $component,
                    );
                } catch (HydrationException $e) {
                    // swallow this: it's bad data from the user
                }
            }

            /*
             | 4) Set UPDATED "writable paths" data for this LiveProp.
             */
            $updatedWritablePaths = $this->calculateWritablePaths($propMetadata, $propertyValue, $dehydratedUpdatedProps, $frontendName, \get_class($component));
            $propertyValue = $this->setWritablePaths(
                $updatedWritablePaths,
                $frontendName,
                $propertyValue,
                $dehydratedUpdatedProps,
            );

            try {
                $this->propertyAccessor->setValue($component, $propMetadata->getName(), $propertyValue);
            } catch (PropertyAccessExceptionInterface $exception) {
                // This is a writable field. The user likely sent a value that was
                // unexpected and can't be set - e.g. a string field for an `int` property.
                // We ignore this, and allow the original value to remain set.
            }
        }

        foreach (AsLiveComponent::postHydrateMethods($component) as $method) {
            $component->{$method->name}();
        }

        return $attributes;
    }

    public function addChecksumToData(array $data): array
    {
        $data[self::CHECKSUM_KEY] = $this->calculateChecksum($data);

        return $data;
    }

    private static function coerceStringValue(string $value, string $type, bool $allowsNull): int|float|bool|null
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

    private function calculateChecksum(array $dehydratedPropsData): ?string
    {
        // sort so it is always consistent (frontend could have re-ordered data)
        ksort($dehydratedPropsData);

        return base64_encode(hash_hmac('sha256', json_encode($dehydratedPropsData), $this->secret, true));
    }

    private function verifyChecksum(array $identifierPops, string $error = 'Invalid checksum sent when updating the live component.'): void
    {
        if (!\array_key_exists(self::CHECKSUM_KEY, $identifierPops)) {
            throw new HydrationException(sprintf('Missing %s. key', self::CHECKSUM_KEY));
        }
        $sentChecksum = $identifierPops[self::CHECKSUM_KEY];
        unset($identifierPops[self::CHECKSUM_KEY]);

        $expectedChecksum = $this->calculateChecksum($identifierPops);

        if (hash_equals($expectedChecksum, $sentChecksum)) {
            return;
        }

        throw new HydrationException($error);
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

    private function setWritablePaths(array $writablePaths, string $frontendPropName, mixed $propertyValue, DehydratedProps $props): mixed
    {
        if (0 === \count($writablePaths)) {
            return $propertyValue;
        }

        // e.g. the value is null right now, so skip writable props
        if (!\is_object($propertyValue) && !\is_array($propertyValue)) {
            return $propertyValue;
        }

        foreach ($writablePaths as $writablePath) {
            if (!$props->hasNestedPathValue($frontendPropName, $writablePath)) {
                continue;
            }

            $writablePathData = $props->getNestedPathValue($frontendPropName, $writablePath);

            try {
                $this->propertyAccessor->setValue(
                    $propertyValue,
                    $this->adjustPropertyPathForData($propertyValue, $writablePath),
                    $writablePathData
                );
            } catch (PropertyAccessExceptionInterface $exception) {
                // The user likely sent bad data (e.g. string for a number field).
                // Simply fail to set the field and use the previous value
                // (this can also happen with original, writable data - e.g. if
                // a property is "null", but the setter method doesn't allow null).
            }
        }

        return $propertyValue;
    }

    private function dehydrateValue(mixed $value, LivePropMetadata $propMetadata, object $component): mixed
    {
        if ($method = $propMetadata->dehydrateMethod()) {
            if (!method_exists($component, $method)) {
                throw new \LogicException(sprintf('The "%s" component has a dehydrateMethod of "%s" but the method does not exist.', \get_class($component), $method));
            }

            return $component->$method($value);
        }

        if ($propMetadata->useSerializerForHydration()) {
            return $this->normalizer->normalize($value, 'json', $propMetadata->serializationContext());
        }

        if (\is_bool($value) || null === $value || is_numeric($value) || \is_string($value)) {
            return $value;
        }

        if (\is_array($value)) {
            if ($propMetadata->collectionValueType() && Type::BUILTIN_TYPE_OBJECT === $propMetadata->collectionValueType()->getBuiltinType()) {
                $collectionClass = $propMetadata->collectionValueType()->getClassName();
                foreach ($value as $key => $objectItem) {
                    if (!$objectItem instanceof $collectionClass) {
                        throw new \LogicException(sprintf('The LiveProp "%s" on component "%s" is an array. We determined the array is full of %s objects, but at least on key had a different value of %s', $propMetadata->getName(), \get_class($component), $collectionClass, get_debug_type($objectItem)));
                    }

                    $value[$key] = $this->dehydrateObjectValue($objectItem, $collectionClass, $propMetadata->getFormat(), \get_class($component), sprintf('%s.%s', $propMetadata->getName(), $key));
                }
            }

            if (!$this->isValueValidDehydratedValue($value)) {
                $badKeys = $this->getNonScalarKeys($value, $propMetadata->getName());
                $badKeysText = implode(', ', array_map(fn ($key) => sprintf('%s: %s', $key, $badKeys[$key]), array_keys($badKeys)));

                throw new \LogicException(sprintf('The LiveProp "%s" on component "%s" is an array, but it contains one or more keys that are not scalars: %s', $propMetadata->getName(), \get_class($component), $badKeysText));
            }

            return $value;
        }

        if (!\is_object($value)) {
            throw new \LogicException(sprintf('Unable to dehydrate value of type "%s" for property "%s" on component "%s". Change this to a simpler type of an object that can be dehydrated. Or set the hydrateWith/dehydrateWith options in LiveProp or set "useSerializerForHydration: true" on the LiveProp to use the serializer.', get_debug_type($value), $propMetadata->getName(), \get_class($component)));
        }

        if (!$propMetadata->getType() || $propMetadata->isBuiltIn()) {
            throw new \LogicException(sprintf('The "%s" property on component "%s" is missing its property-type. Add the "%s" type so the object can be hydrated later.', $propMetadata->getName(), \get_class($component), \get_class($value)));
        }

        // at this point, we have an object and can assume $propMetadata->getType()
        // is set correctly (needed for hydration later)

        return $this->dehydrateObjectValue($value, $propMetadata->getType(), $propMetadata->getFormat(), \get_class($component), $propMetadata->getName());
    }

    private function dehydrateObjectValue(object $value, string $classType, ?string $dateFormat, string $componentClassForError, string $propertyPathForError): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format($dateFormat ?: \DateTimeInterface::RFC3339);
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        foreach ($this->hydrationExtensions as $extension) {
            if ($extension->supports($classType)) {
                return $extension->dehydrate($value);
            }
        }

        throw new \LogicException(sprintf('Unable to dehydrate value of type "%s" for property "%s" on component "%s". Either (1) change this to a simpler value, (2) add the hydrateWith/dehydrateWith options to LiveProp or (3) set "useSerializerForHydration: true" on the LiveProp.', \get_class($value), $propertyPathForError, $componentClassForError));
    }

    private function hydrateValue(mixed $value, LivePropMetadata $propMetadata, object $component): mixed
    {
        if ($propMetadata->hydrateMethod()) {
            if (!method_exists($component, $propMetadata->hydrateMethod())) {
                throw new \LogicException(sprintf('The "%s" component has a hydrateMethod of "%s" but the method does not exist.', \get_class($component), $propMetadata->hydrateMethod()));
            }

            return $component->{$propMetadata->hydrateMethod()}($value);
        }

        if ($propMetadata->useSerializerForHydration()) {
            return $this->normalizer->denormalize($value, $propMetadata->getType(), 'json', $propMetadata->serializationContext());
        }

        if ($propMetadata->collectionValueType() && Type::BUILTIN_TYPE_OBJECT === $propMetadata->collectionValueType()->getBuiltinType()) {
            $collectionClass = $propMetadata->collectionValueType()->getClassName();
            foreach ($value as $key => $objectItem) {
                $value[$key] = $this->hydrateObjectValue($objectItem, $collectionClass, true, \get_class($component), sprintf('%s.%s', $propMetadata->getName(), $key));
            }
        }

        // no type? no hydration
        if (!$propMetadata->getType()) {
            return $value;
        }

        if (null === $value) {
            return null;
        }

        if (\is_string($value) && $propMetadata->isBuiltIn() && \in_array($propMetadata->getType(), ['int', 'float', 'bool'], true)) {
            return self::coerceStringValue($value, $propMetadata->getType(), $propMetadata->allowsNull());
        }

        // for all other built-ins: int, boolean, array, return as is
        if ($propMetadata->isBuiltIn()) {
            return $value;
        }

        return $this->hydrateObjectValue($value, $propMetadata->getType(), $propMetadata->allowsNull(), \get_class($component), $propMetadata->getName());
    }

    private function hydrateObjectValue(mixed $value, string $className, bool $allowsNull, string $componentClassForError, string $propertyPathForError): ?object
    {
        // enum
        if (is_a($className, \BackedEnum::class, true)) {
            if ($allowsNull && !\in_array($value, array_map(fn (\BackedEnum $e) => $e->value, $className::cases()))) {
                return null;
            }

            return $className::tryFrom($value);
        }

        // date time
        if (is_a($className, \DateTimeInterface::class, true)) {
            if (\DateTimeInterface::class === $className) {
                $className = \DateTimeImmutable::class;
            }

            if (!\is_string($value)) {
                throw new BadRequestHttpException(sprintf('The model path "%s" was sent an invalid data type "%s" for a date.', $propertyPathForError, get_debug_type($value)));
            }

            return new $className($value);
        }

        foreach ($this->hydrationExtensions as $extension) {
            if ($extension->supports($className)) {
                return $extension->hydrate($value, $className);
            }
        }

        throw new HydrationException(sprintf('Unable to hydrate value of type "%s" for property "%s" on component "%s". Change this to a simpler value, add the hydrateWith/dehydrateWith options to LiveProp or set "useSerializerForHydration: true" on the LiveProp to use the serializer..', $className, $propertyPathForError, $componentClassForError));
    }

    private function isValueValidDehydratedValue(mixed $value): bool
    {
        if (\is_bool($value) || null === $value || is_numeric($value) || \is_string($value)) {
            return true;
        }

        if (!\is_array($value)) {
            return false;
        }

        foreach ($value as $v) {
            if (!$this->isValueValidDehydratedValue($v)) {
                return false;
            }
        }

        return true;
    }

    private function getNonScalarKeys(array $value, string $path = ''): array
    {
        $nonScalarKeys = [];
        foreach ($value as $k => $v) {
            if (\is_array($v)) {
                $nonScalarKeys = array_merge($nonScalarKeys, $this->getNonScalarKeys($v, sprintf('%s.%s', $path, $k)));
                continue;
            }

            if (!$this->isValueValidDehydratedValue($v)) {
                $nonScalarKeys[sprintf('%s.%s', $path, $k)] = get_debug_type($v);
            }
        }

        return $nonScalarKeys;
    }

    /**
     * Allows for specific keys to be written to a "fully-writable" array.
     *
     * For example, suppose a property called "options" is an array and its identity
     * is writable. If the user sends an updated field called "options.name",
     * we need to set the "name" key on the "options" array, even if "name"
     * isn't explicitly a writable path.
     */
    private function calculateWritablePaths(LivePropMetadata $propMetadata, mixed $propertyValue, DehydratedProps $props, string $frontendPropName, string $componentClass): array
    {
        $writablePaths = $propMetadata->writablePaths();
        if (\is_array($propertyValue) && $propMetadata->isIdentityWritable()) {
            $writablePaths = array_merge($writablePaths, $props->getNestedPathsForProperty($frontendPropName));
        }

        $extraSentWritablePaths = $props->calculateUnexpectedNestedPathsForProperty($frontendPropName, $writablePaths);

        if (\count($extraSentWritablePaths) > 0) {
            // we could show multiple fields here in the message
            throw new HydrationException(sprintf('The model "%s.%s" was sent for update, but it is not writable. Try adding "writable: [\'%s\']" to the $%s property in %s.', $frontendPropName, $extraSentWritablePaths[0], $extraSentWritablePaths[0], $propMetadata->getName(), $componentClass));
        }

        return $writablePaths;
    }

    private function combineAndValidateProps(array $props, array $updatedPropsFromParent): DehydratedProps
    {
        $dehydratedOriginalProps = DehydratedProps::createFromPropsArray($props);
        $this->verifyChecksum($dehydratedOriginalProps->getProps());
        $dehydratedOriginalProps->removePropValue(self::CHECKSUM_KEY);

        // if a parent component is requesting some updates to the props, verify
        // their checksum and apply them as "original props"
        if (\count($updatedPropsFromParent) > 0) {
            $this->verifyChecksum($updatedPropsFromParent, 'Invalid checksum for the data sent from the parent component.');
            unset($updatedPropsFromParent[self::CHECKSUM_KEY]);
            foreach ($updatedPropsFromParent as $key => $value) {
                $dehydratedOriginalProps->addPropValue($key, $value);
            }
        }

        return $dehydratedOriginalProps;
    }
}
