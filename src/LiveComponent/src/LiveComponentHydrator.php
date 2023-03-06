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
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Mapping\AttributeMetadataInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Exception\HydrationException;
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

    private array $componentSerializationMetadatas = [];

    public function __construct(
        private NormalizerInterface|DenormalizerInterface $normalizer,
        private PropertyAccessorInterface $propertyAccessor,
        private PropertyTypeExtractorInterface $propertyTypeExtractor,
        private ClassMetadataFactoryInterface $serializerMetadataFactory,
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

            try {
                $rawPropertyValue = $this->propertyAccessor->getValue($component, $propertyName);
            } catch (UninitializedPropertyException $exception) {
                throw new \LogicException(sprintf('The "%s" property on the "%s" component is uninitialized. Did you forget to pass this into the component?', $propertyName, \get_class($component)), 0, $exception);
            }

            $dehydratedValue = $rawPropertyValue;

            $normalizationContext = $this->getNormalizationContext($component, $propMetadata->getName());
            if ($method = $propMetadata->dehydrateMethod()) {
                if (!method_exists($component, $method)) {
                    throw new \LogicException(sprintf('The "%s" component has a dehydrateMethod of "%s" but the method does not exist.', \get_class($component), $method));
                }
                $dehydratedValue = $component->$method($dehydratedValue);
            } elseif (\is_object($rawPropertyValue)) {
                $dehydratedValue = $this->normalizer->normalize(
                    $dehydratedValue,
                    'json',
                    array_merge([self::LIVE_CONTEXT => true], $normalizationContext)
                );
            }

            if ($propMetadata->isIdentityWritable()) {
                $this->preventArrayDehydratedValueForObjectThatIsWritable($dehydratedValue, $propMetadata->getType(), $propMetadata->isBuiltIn(), $propMetadata->getName(), false);
            }

            $dehydratedProps->addPropValue($frontendName, $dehydratedValue);

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
                        array_merge([self::LIVE_CONTEXT => true], $normalizationContext)
                    );

                    $this->preventArrayDehydratedValueForObjectThatIsWritable($pathValue, $originalPathValueClass, false, sprintf('%s.%s', $propMetadata->getName(), $path), false);
                }

                $dehydratedProps->addNestedProp($frontendName, $path, $pathValue);
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
    public function hydrate(object $component, array $props, array $updatedProps, LiveComponentMetadata $componentMetadata): ComponentAttributes
    {
        $dehydratedOriginalProps = DehydratedProps::createFromPropsArray($props);
        $dehydratedUpdatedProps = DehydratedProps::createFromUpdatedArray($updatedProps);

        $this->verifyChecksum($dehydratedOriginalProps->getProps());
        $dehydratedOriginalProps->removePropValue(self::CHECKSUM_KEY);

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
            | 1) Hydrate and set the "original" data for this LiveProp.
            */
            $propertyValue = $this->hydrateLiveProp(
                $component,
                $propMetadata,
                $dehydratedOriginalProps->getPropValue($frontendName),
            );
            $this->propertyAccessor->setValue($component, $propMetadata->getName(), $propertyValue);

            /*
             | 2) Hydrate and set the "updated" data for this LiveProp if one was sent.
             */
            if ($dehydratedUpdatedProps->hasPropValue($frontendName)) {
                if (!$propMetadata->isIdentityWritable()) {
                    throw new HydrationException(sprintf('The model "%s" was sent for update, but it is not writable. Try adding "writable: true" to the $%s property in %s.', $frontendName, $propMetadata->getName(), \get_class($component)));
                }
                try {
                    $propertyValue = $this->hydrateLiveProp(
                        $component,
                        $propMetadata,
                        $dehydratedUpdatedProps->getPropValue($frontendName),
                    );
                } catch (HydrationException $e) {
                    // swallow this: it's bad data from the user
                }
            }

            /*
             | 3) Hydrate and set any "writable paths" data for this LiveProp.
             */
            if (\is_array($propertyValue) || \is_object($propertyValue)) {
                $propertyValue = $this->hydrateAndSetWritablePaths(
                    $propMetadata,
                    $frontendName,
                    $propertyValue,
                    $dehydratedUpdatedProps,
                    $this->getDenormalizationContext($component, $propMetadata->getName()),
                    \get_class($component),
                );
            }

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

    private function calculateChecksum(array $dehydratedPropsData): ?string
    {
        // sort so it is always consistent (frontend could have re-ordered data)
        ksort($dehydratedPropsData);

        return base64_encode(hash_hmac('sha256', json_encode($dehydratedPropsData), $this->secret, true));
    }

    private function verifyChecksum(array $identifierPops): void
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

        throw new HydrationException('Invalid checksum sent when updating the live component.');
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

        throw new BadRequestHttpException($message);
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

    private function hydrateValue(mixed $value, ?string $type, bool $allowsNull, bool $isBuiltIn, array $denormalizationContext, string $fullPathForErrors, bool $isWritable, string $componentClass): mixed
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
            $this->preventArrayDehydratedValueForObjectThatIsWritable($value, $type, $isBuiltIn, $fullPathForErrors, true);
        }

        try {
            return $this->normalizer->denormalize(
                $value,
                $type,
                'json',
                array_merge([self::LIVE_CONTEXT => true], $denormalizationContext)
            );
        } catch (ExceptionInterface $exception) {
            $json = json_encode($value);
            $message = sprintf(
                'The normalizer was used to hydrate/denormalize the "%s" property on your "%s" live component, but it failed: %s',
                $fullPathForErrors,
                $componentClass,
                $exception->getMessage()
            );

            // unless the data is gigantic, include it in the error to help
            if (\strlen($json) < 1000) {
                $message .= sprintf(' The data sent from the frontend was: %s', $json);
            }

            throw new HydrationException($message, $exception);
        }
    }

    private function hydrateLiveProp(object $component, Metadata\LivePropMetadata $propMetadata, mixed $dehydratedProp): mixed
    {
        if ($propMetadata->hydrateMethod()) {
            if (!method_exists($component, $propMetadata->hydrateMethod())) {
                throw new \LogicException(sprintf('The "%s" component has a hydrateMethod of "%s" but the method does not exist.', \get_class($component), $propMetadata->hydrateMethod()));
            }

            return $component->{$propMetadata->hydrateMethod()}($dehydratedProp);
        }

        return $this->hydrateValue(
            $dehydratedProp,
            $propMetadata->getType(),
            $propMetadata->allowsNull(),
            $propMetadata->isBuiltIn(),
            $this->getDenormalizationContext($component, $propMetadata->getName()),
            $propMetadata->getName(),
            $propMetadata->isIdentityWritable(),
            \get_class($component),
        );
    }

    private function hydrateAndSetWritablePaths(LivePropMetadata $propMetadata, string $frontendPropName, array|object $propertyValue, DehydratedProps $props, array $denormalizationContext, string $componentClass): array|object
    {
        /*
         | Allows for specific keys to be written to a "fully-writable" array.
         |
         | For example, suppose a property called "options" is an array and its identity
         | is writable. If the user sends an updated field called "options.name",
         | we need to set the "name" key on the "options" array, even if "name"
         | isn't explicitly a writable path.
         */
        $writablePaths = $propMetadata->writablePaths();
        if (\is_array($propertyValue) && $propMetadata->isIdentityWritable()) {
            $writablePaths = array_merge($writablePaths, $props->getNestedPathsForProperty($frontendPropName));
        }

        $extraSentWritablePaths = $props->calculateUnexpectedNestedPathsForProperty($frontendPropName, $writablePaths);

        if (\count($extraSentWritablePaths) > 0) {
            // we could show multiple fields here in the message
            throw new HydrationException(sprintf('The model "%s.%s" was sent for update, but it is not writable. Try adding "writable: [\'%s\']" to the $%s property in %s.', $frontendPropName, $extraSentWritablePaths[0], $extraSentWritablePaths[0], $propMetadata->getName(), $componentClass));
        }

        foreach ($writablePaths as $writablePath) {
            if (!$props->hasNestedPathValue($frontendPropName, $writablePath)) {
                continue;
            }

            $writablePathData = $props->getNestedPathValue($frontendPropName, $writablePath);

            // smarter hydration currently only supported for top-level writable
            // e.g. writablePaths: ['post.createdAt'] is not supported.
            if (0 === substr_count($writablePath, '.') && \is_object($propertyValue)) {
                $types = $this->propertyTypeExtractor->getTypes(\get_class($propertyValue), $writablePath);
                $type = null === $types ? null : $types[0] ?? null;
                $isBuiltIn = $type ? Type::BUILTIN_TYPE_OBJECT !== $type->getBuiltinType() : false;
                $typeString = null;
                if ($type) {
                    $typeString = $isBuiltIn ? $type->getBuiltinType() : $type->getClassName();
                }

                try {
                    $writablePathData = $this->hydrateValue(
                        $writablePathData,
                        $typeString,
                        $type ? $type->isNullable() : true,
                        $isBuiltIn,
                        $denormalizationContext,
                        sprintf('%s.%s', $frontendPropName, $writablePath),
                        true,
                        $componentClass,
                    );
                } catch (HydrationException $exception) {
                    // swallow problems hydrating user-sent data
                    continue;
                }
            }

            try {
                $this->propertyAccessor->setValue(
                    $propertyValue,
                    $this->adjustPropertyPathForData($propertyValue, $writablePath),
                    $writablePathData
                );
            } catch (PropertyAccessExceptionInterface $e) {
                // swallow problems setting user-sent data
            }
        }

        return $propertyValue;
    }

    private function getSerializationAttributeMetadata(object $component, string $propertyName): ?AttributeMetadataInterface
    {
        if (!isset($this->componentSerializationMetadatas[\get_class($component)])) {
            $metadata = $this->serializerMetadataFactory->getMetadataFor($component);

            foreach ($metadata->getAttributesMetadata() as $attributeMetadata) {
                $this->componentSerializationMetadatas[\get_class($component)][$attributeMetadata->getName()] = $attributeMetadata;
            }
        }

        if (!isset($this->componentSerializationMetadatas[\get_class($component)][$propertyName])) {
            return null;
        }

        return $this->componentSerializationMetadatas[\get_class($component)][$propertyName];
    }

    private function getNormalizationContext(object $component, string $propertyName): array
    {
        $attributeMetadata = $this->getSerializationAttributeMetadata($component, $propertyName);
        // passing [] for groups - not sure if we need to be smarter
        return $attributeMetadata ? $attributeMetadata->getNormalizationContextForGroups([]) : [];
    }

    private function getDenormalizationContext(object $component, string $propertyName): array
    {
        $attributeMetadata = $this->getSerializationAttributeMetadata($component, $propertyName);
        // passing [] for groups - not sure if we need to be smarter
        return $attributeMetadata ? $attributeMetadata->getDenormalizationContextForGroups([]) : [];
    }
}
