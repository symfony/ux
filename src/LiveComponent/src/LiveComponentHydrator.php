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
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Exception\HydrationException;
use Symfony\UX\LiveComponent\Hydration\HydrationExtensionInterface;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadata;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadataFactory;
use Symfony\UX\LiveComponent\Metadata\LivePropMetadata;
use Symfony\UX\LiveComponent\Util\DehydratedProps;
use Symfony\UX\TwigComponent\ComponentAttributes;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class LiveComponentHydrator
{
    private const ATTRIBUTES_KEY = '@attributes';
    private const CHECKSUM_KEY = '@checksum';

    /**
     * @param iterable<HydrationExtensionInterface> $hydrationExtensions
     */
    public function __construct(
        private iterable $hydrationExtensions,
        private PropertyAccessorInterface $propertyAccessor,
        private LiveComponentMetadataFactory $liveComponentMetadataFactory,
        private NormalizerInterface|DenormalizerInterface|null $serializer,
        private string $secret,
    ) {
    }

    public function dehydrate(object $component, ComponentAttributes $attributes, LiveComponentMetadata $componentMetadata): DehydratedProps
    {
        foreach (AsLiveComponent::preDehydrateMethods($component) as $method) {
            $component->{$method->name}();
        }

        $takenFrontendPropertyNames = [];

        $dehydratedProps = new DehydratedProps();
        foreach ($componentMetadata->getAllLivePropsMetadata($component) as $propMetadata) {
            $propertyName = $propMetadata->getName();
            $frontendName = $propMetadata->calculateFieldName($component, $propertyName);

            if (isset($takenFrontendPropertyNames[$frontendName])) {
                $message = \sprintf('The field name "%s" cannot be used by multiple LiveProp properties in a component. Currently, both "%s" and "%s" are trying to use it in "%s".', $frontendName, $takenFrontendPropertyNames[$frontendName], $propertyName, $component::class);

                if ($frontendName === $takenFrontendPropertyNames[$frontendName] || $frontendName === $propertyName) {
                    $message .= \sprintf(' Try adding LiveProp(fieldName="somethingElse") for the "%s" property to avoid this.', $frontendName);
                }

                throw new \LogicException($message);
            }

            $takenFrontendPropertyNames[$frontendName] = $propertyName;

            // 1) Dehydrate main value
            try {
                $rawPropertyValue = $this->propertyAccessor->getValue($component, $propertyName);
            } catch (UninitializedPropertyException $exception) {
                throw new \LogicException(\sprintf('The "%s" property on the "%s" component is uninitialized. Did you forget to pass this into the component?', $propertyName, $component::class), 0, $exception);
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
                        throw new \LogicException(\sprintf('The writable path "%s" does not exist on the "%s" property of the "%s" component.', $path, $propertyName, $component::class), 0, $e);
                    } catch (PropertyAccessExceptionInterface $e) {
                        throw new \LogicException(\sprintf('The writable path "%s" on the "%s" property of the "%s" component could not be read: %s', $path, $propertyName, $component::class, $e->getMessage()), 0, $e);
                    }

                    // TODO: maybe we allow support the same types as LiveProps later
                    if (!$this->isValueValidDehydratedValue($pathValue)) {
                        throw new \LogicException(\sprintf('The writable path "%s" on the "%s" property of the "%s" component must be a scalar or array of scalars.', $path, $propertyName, $component::class));
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

        $needProcessOnUpdatedHooks = [];

        foreach ($componentMetadata->getAllLivePropsMetadata($component) as $propMetadata) {
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
            $originalWritablePaths = $this->calculateWritablePaths($propMetadata, $propertyValue, $dehydratedOriginalProps, $frontendName, $component::class);
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
                    throw new HydrationException(\sprintf('The model "%s" was sent for update, but it is not writable. Try adding "writable: true" to the $%s property in %s.', $frontendName, $propMetadata->getName(), $component::class));
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
            $updatedWritablePaths = $this->calculateWritablePaths($propMetadata, $propertyValue, $dehydratedUpdatedProps, $frontendName, $component::class);
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

            if ($propMetadata->onUpdated()) {
                $needProcessOnUpdatedHooks[$frontendName] = $propMetadata;
            }
        }

        // Run 'onUpdated' hooks after all props have been initialized.
        foreach ($needProcessOnUpdatedHooks as $frontendName => $propMetadata) {
            $this->processOnUpdatedHook($component, $frontendName, $propMetadata, $dehydratedUpdatedProps, $dehydratedOriginalProps);
        }

        foreach (AsLiveComponent::postHydrateMethods($component) as $method) {
            $component->{$method->name}();
        }

        return $attributes;
    }

    /**
     * Hydrate a value from a dehydrated value.
     *
     * Depending on the prop configuration, the value may be hydrated by a custom method or the Serializer component.
     *
     * @internal
     *
     * @throws SerializerExceptionInterface
     */
    public function hydrateValue(mixed $value, LivePropMetadata $propMetadata, object $parentObject): mixed
    {
        if ($propMetadata->hydrateMethod()) {
            if (!method_exists($parentObject, $propMetadata->hydrateMethod())) {
                throw new \LogicException(\sprintf('The "%s" object has a hydrateMethod of "%s" but the method does not exist.', $parentObject::class, $propMetadata->hydrateMethod()));
            }

            return $parentObject->{$propMetadata->hydrateMethod()}($value);
        }

        if ($propMetadata->useSerializerForHydration()) {
            if (!interface_exists(DenormalizerInterface::class)) {
                throw new \LogicException(\sprintf('The LiveProp "%s" on component "%s" has "useSerializerForHydration: true", but the Serializer component is not installed. Try running "composer require symfony/serializer".', $propMetadata->getName(), $parentObject::class));
            }
            if (null === $this->serializer) {
                throw new \LogicException(\sprintf('The LiveProp "%s" on component "%s" has "useSerializerForHydration: true", but no serializer has been set.', $propMetadata->getName(), $parentObject::class));
            }
            if (!$this->serializer instanceof DenormalizerInterface) {
                throw new \LogicException(\sprintf('The LiveProp "%s" on component "%s" has "useSerializerForHydration: true", but the given serializer does not implement DenormalizerInterface.', $propMetadata->getName(), $parentObject::class));
            }

            if ($propMetadata->collectionValueType()) {
                $builtInType = $propMetadata->collectionValueType()->getBuiltinType();
                if (Type::BUILTIN_TYPE_OBJECT === $builtInType) {
                    $type = $propMetadata->collectionValueType()->getClassName().'[]';
                } else {
                    $type = $builtInType.'[]';
                }
            } else {
                $type = $propMetadata->getType();
            }

            if (null === $type) {
                throw new \LogicException(\sprintf('The "%s::%s" object should be hydrated with the Serializer, but no type could be guessed.', $parentObject::class, $propMetadata->getName()));
            }

            return $this->serializer->denormalize($value, $type, 'json', $propMetadata->serializationContext());
        }

        if ($propMetadata->collectionValueType() && Type::BUILTIN_TYPE_OBJECT === $propMetadata->collectionValueType()->getBuiltinType()) {
            $collectionClass = $propMetadata->collectionValueType()->getClassName();
            foreach ($value as $key => $objectItem) {
                $value[$key] = $this->hydrateObjectValue($objectItem, $collectionClass, true, $propMetadata->getFormat(), $parentObject::class, \sprintf('%s.%s', $propMetadata->getName(), $key), $parentObject);
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

        return $this->hydrateObjectValue($value, $propMetadata->getType(), $propMetadata->allowsNull(), $propMetadata->getFormat(), $parentObject::class, $propMetadata->getName(), $parentObject);
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
            'bool' => self::coerceStringToBoolean($value),
            default => throw new \LogicException(\sprintf('Cannot coerce value "%s" to type "%s"', $value, $type)),
        };
    }

    private static function coerceStringToBoolean(string $value): bool
    {
        $booleanMap = [
            '0' => false,
            '1' => true,
            'false' => false,
            'true' => true,
        ];

        return $booleanMap[$value] ?? (bool) $value;
    }

    private function calculateChecksum(array $dehydratedPropsData): ?string
    {
        // sort so it is always consistent (frontend could have re-ordered data)
        $this->recursiveKeySort($dehydratedPropsData);

        return base64_encode(hash_hmac('sha256', json_encode($dehydratedPropsData), $this->secret, true));
    }

    private function verifyChecksum(array $identifierPops, string $error = 'Invalid checksum sent when updating the live component.'): void
    {
        if (!\array_key_exists(self::CHECKSUM_KEY, $identifierPops)) {
            throw new HydrationException(\sprintf('Missing %s. key', self::CHECKSUM_KEY));
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
                $finalPropertyPath .= \sprintf('[%s]', $part);
                $currentValue = $this->propertyAccessor->getValue($rawPropertyValue, $finalPropertyPath);

                continue;
            }

            if ('' !== $finalPropertyPath) {
                $finalPropertyPath .= '.';
            }

            $finalPropertyPath .= $part;

            if (null !== $currentValue) {
                $currentValue = $this->propertyAccessor->getValue($rawPropertyValue, $finalPropertyPath);
            }
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

    private function dehydrateValue(mixed $value, LivePropMetadata $propMetadata, object $parentObject): mixed
    {
        if ($method = $propMetadata->dehydrateMethod()) {
            if (!method_exists($parentObject, $method)) {
                throw new \LogicException(\sprintf('The dehydration failed for class "%s" because the "%s" method does not exist.', $parentObject::class, $method));
            }

            return $parentObject->$method($value);
        }

        if ($propMetadata->useSerializerForHydration()) {
            if (!interface_exists(NormalizerInterface::class)) {
                throw new \LogicException(\sprintf('The LiveProp "%s" on component "%s" has "useSerializerForHydration: true", but the Serializer component is not installed. Try running "composer require symfony/serializer".', $propMetadata->getName(), $parentObject::class));
            }
            if (null === $this->serializer) {
                throw new \LogicException(\sprintf('The LiveProp "%s" on component "%s" has "useSerializerForHydration: true", but no serializer has been set.', $propMetadata->getName(), $parentObject::class));
            }
            if (!$this->serializer instanceof NormalizerInterface) {
                throw new \LogicException(\sprintf('The LiveProp "%s" on component "%s" has "useSerializerForHydration: true", but the given serializer does not implement NormalizerInterface.', $propMetadata->getName(), $parentObject::class));
            }

            return $this->serializer->normalize($value, 'json', $propMetadata->serializationContext());
        }

        if (\is_bool($value) || null === $value || is_numeric($value) || \is_string($value)) {
            return $value;
        }

        if (\is_array($value)) {
            if ($propMetadata->collectionValueType() && Type::BUILTIN_TYPE_OBJECT === $propMetadata->collectionValueType()->getBuiltinType()) {
                $collectionClass = $propMetadata->collectionValueType()->getClassName();
                foreach ($value as $key => $objectItem) {
                    if (!$objectItem instanceof $collectionClass) {
                        throw new \LogicException(\sprintf('The LiveProp "%s" on component "%s" is an array. We determined the array is full of %s objects, but at least one key had a different value of %s', $propMetadata->getName(), $parentObject::class, $collectionClass, get_debug_type($objectItem)));
                    }

                    $value[$key] = $this->dehydrateObjectValue($objectItem, $collectionClass, $propMetadata->getFormat(), $parentObject);
                }
            }

            if (!$this->isValueValidDehydratedValue($value)) {
                throw new \LogicException(throw new \LogicException(\sprintf('Unable to dehydrate value of type "%s" for property "%s" on component "%s". Change this to a simpler type of an object that can be dehydrated. Or set the hydrateWith/dehydrateWith options in LiveProp or set "useSerializerForHydration: true" on the LiveProp to use the serializer.', get_debug_type($value), $propMetadata->getName(), $parentObject::class)));
            }

            return $value;
        }

        if (!\is_object($value)) {
            throw new \LogicException(\sprintf('Unable to dehydrate value of type "%s" for property "%s" on component "%s". Change this to a simpler type of an object that can be dehydrated. Or set the hydrateWith/dehydrateWith options in LiveProp or set "useSerializerForHydration: true" on the LiveProp to use the serializer.', get_debug_type($value), $propMetadata->getName(), $parentObject::class));
        }

        if (!$propMetadata->getType() || $propMetadata->isBuiltIn()) {
            throw new \LogicException(\sprintf('The "%s" property on component "%s" is missing its property-type. Add the "%s" type so the object can be hydrated later.', $propMetadata->getName(), $parentObject::class, $value::class));
        }

        // at this point, we have an object and can assume $propMetadata->getType()
        // is set correctly (needed for hydration later)

        return $this->dehydrateObjectValue($value, $propMetadata->getType(), $propMetadata->getFormat(), $parentObject);
    }

    private function dehydrateObjectValue(object $value, string $classType, ?string $dateFormat, object $parentObject): mixed
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

        if (interface_exists($classType)) {
            throw new \LogicException(\sprintf('Cannot dehydrate value typed as interface "%s" on component "%s". Change this to a concrete type that can be dehydrated. Or set the hydrateWith/dehydrateWith options in LiveProp or set "useSerializerForHydration: true" on the LiveProp to use the serializer.', get_debug_type($value), $parentObject::class));
        }

        $dehydratedObjectValues = [];
        foreach ((new PropertyInfoExtractor([new ReflectionExtractor()]))->getProperties($classType) as $property) {
            $propertyValue = $this->propertyAccessor->getValue($value, $property);
            $propMetadata = $this->liveComponentMetadataFactory->createLivePropMetadata($classType, $property, new \ReflectionProperty($classType, $property), new LiveProp());
            $dehydratedObjectValues[$property] = $this->dehydrateValue($propertyValue, $propMetadata, $parentObject);
        }

        return $dehydratedObjectValues;
    }

    private function hydrateObjectValue(mixed $value, string $className, bool $allowsNull, ?string $dateFormat, string $componentClassForError, string $propertyPathForError, object $component): ?object
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
                throw new BadRequestHttpException(\sprintf('The model path "%s" was sent an invalid data type "%s" for a date.', $propertyPathForError, get_debug_type($value)));
            }

            if (null !== $dateFormat) {
                return $className::createFromFormat($dateFormat, $value) ?: throw new BadRequestHttpException(\sprintf('The model path "%s" was sent invalid date data "%s" or in an invalid format. Make sure it\'s a valid date and it matches the expected format "%s".', $propertyPathForError, $value, $dateFormat));
            }

            return new $className($value);
        }

        foreach ($this->hydrationExtensions as $extension) {
            if ($extension->supports($className)) {
                return $extension->hydrate($value, $className);
            }
        }

        if (interface_exists($className)) {
            throw new \LogicException(\sprintf('Cannot hydrate value typed as interface "%s" on component "%s". Change this to a concrete type that can be hydrated. Or set the hydrateWith/dehydrateWith options in LiveProp or set "useSerializerForHydration: true" on the LiveProp to use the serializer.', $className, $component::class));
        }

        if (\is_array($value)) {
            $object = new $className();
            foreach ($value as $propertyName => $propertyValue) {
                $reflectionClass = new \ReflectionClass($className);
                $property = $reflectionClass->getProperty($propertyName);
                $propMetadata = $this->liveComponentMetadataFactory->createLivePropMetadata($className, $propertyName, $property, new LiveProp());
                $this->propertyAccessor->setValue($object, $propertyName, $this->hydrateValue($propertyValue, $propMetadata, $component));
            }

            return $object;
        }

        throw new HydrationException(\sprintf('Unable to hydrate value of type "%s" for property "%s" on component "%s". it looks like something went wrong by trying to guess your property types.', $className, $propertyPathForError, $componentClassForError));
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
            throw new HydrationException(\sprintf('The model "%s.%s" was sent for update, but it is not writable. Try adding "writable: [\'%s\']" to the $%s property in %s.', $frontendPropName, $extraSentWritablePaths[0], $extraSentWritablePaths[0], $propMetadata->getName(), $componentClass));
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

    private function recursiveKeySort(array &$data): void
    {
        foreach ($data as &$value) {
            if (\is_array($value)) {
                $this->recursiveKeySort($value);
            }
        }
        ksort($data);
    }

    private function ensureOnUpdatedMethodExists(object $component, string $methodName): void
    {
        if (method_exists($component, $methodName)) {
            return;
        }

        throw new \Exception(\sprintf('Method "%s:%s()" specified as LiveProp "onUpdated" hook does not exist.', $component::class, $methodName));
    }

    /**
     * A special hook that will be called if the LiveProp was changed
     * and $onUpdated argument is set on its attribute.
     */
    private function processOnUpdatedHook(object $component, string $frontendName, LivePropMetadata $propMetadata, DehydratedProps $dehydratedUpdatedProps, DehydratedProps $dehydratedOriginalProps): void
    {
        $onUpdated = $propMetadata->onUpdated();
        if (\is_string($onUpdated)) {
            $onUpdated = [LiveProp::IDENTITY => $onUpdated];
        }

        foreach ($onUpdated as $propName => $funcName) {
            if (LiveProp::IDENTITY === $propName) {
                if (!$dehydratedUpdatedProps->hasPropValue($frontendName)) {
                    continue;
                }

                $this->ensureOnUpdatedMethodExists($component, $funcName);
                $propertyOldValue = $this->hydrateValue(
                    $dehydratedOriginalProps->getPropValue($frontendName),
                    $propMetadata,
                    $component,
                );
                $component->{$funcName}($propertyOldValue);

                continue;
            }

            $key = \sprintf('%s.%s', $frontendName, $propName);
            if (!$dehydratedUpdatedProps->hasPropValue($key)) {
                continue;
            }

            $this->ensureOnUpdatedMethodExists($component, $funcName);
            $propertyOldValue = $dehydratedOriginalProps->getPropValue($key);
            $component->{$funcName}($propertyOldValue);
        }
    }
}
