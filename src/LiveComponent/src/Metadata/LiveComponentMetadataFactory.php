<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Metadata;

use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\TwigComponent\ComponentFactory;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @experimental
 *
 * @internal
 */
class LiveComponentMetadataFactory
{
    public function __construct(
        private ComponentFactory $componentFactory,
        private PropertyTypeExtractorInterface $propertyTypeExtractor,
    ) {
    }

    public function getMetadata(string $name): LiveComponentMetadata
    {
        $componentMetadata = $this->componentFactory->metadataFor($name);

        $reflectionClass = new \ReflectionClass($componentMetadata->getClass());
        $livePropsMetadata = $this->createPropMetadatas($reflectionClass);

        return new LiveComponentMetadata($componentMetadata, $livePropsMetadata);
    }

    /**
     * @return LivePropMetadata[]
     *
     * @internal
     */
    public function createPropMetadatas(\ReflectionClass $class): array
    {
        $metadatas = [];

        foreach (self::propertiesFor($class) as $property) {
            if (!$attribute = $property->getAttributes(LiveProp::class)[0] ?? null) {
                continue;
            }

            if (isset($metadatas[$property->getName()])) {
                // property name was already used
                continue;
            }

            $metadatas[$property->getName()] = $this->createLivePropMetadata($class->getName(), $property->getName(), $property, $attribute);
        }

        return array_values($metadatas);
    }

    public function createLivePropMetadata(string $className, string $propertyName, \ReflectionProperty $property, \ReflectionAttribute $attribute = null): LivePropMetadata
    {
        $collectionValueType = null;
        $infoTypes = $this->propertyTypeExtractor->getTypes($className, $propertyName) ?? [];
        foreach ($infoTypes as $infoType) {
            if ($infoType->isCollection()) {
                foreach ($infoType->getCollectionValueTypes() as $valueType) {
                    $collectionValueType = $valueType;
                    break;
                }
            }
        }

        $type = $property->getType();
        if ($type instanceof \ReflectionUnionType || $type instanceof \ReflectionIntersectionType) {
            throw new \LogicException(sprintf('Union or intersection types are not supported for LiveProps. You may want to change the type of property %s in %s.', $property->getName(), $property->getDeclaringClass()->getName()));
        }

        return new LivePropMetadata(
            $property->getName(),
            null !== $attribute ? $attribute->newInstance() : new LiveProp(true),
            $type?->getName(),
            $type && $type->isBuiltin(),
            !$type || $type->allowsNull(),
            $collectionValueType,
        );
    }

    /**
     * @return \ReflectionProperty[]
     */
    private static function propertiesFor(\ReflectionClass $class): \Traversable
    {
        foreach ($class->getProperties() as $property) {
            yield $property;
        }

        if ($parent = $class->getParentClass()) {
            yield from self::propertiesFor($parent);
        }
    }
}
