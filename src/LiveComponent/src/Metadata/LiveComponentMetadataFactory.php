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

            $collectionValueType = null;
            $infoTypes = $this->propertyTypeExtractor->getTypes($class->getName(), $property->getName()) ?? [];
            foreach ($infoTypes as $infoType) {
                if ($infoType->isCollection()) {
                    foreach ($infoType->getCollectionValueTypes() as $valueType) {
                        $collectionValueType = $valueType;
                        break;
                    }
                }
            }

            $type = $property->getType();
            $metadatas[$property->getName()] = new LivePropMetadata(
                $property->getName(),
                $attribute->newInstance(),
                $type ? $type->getName() : null,
                $type ? $type->isBuiltin() : false,
                $type ? $type->allowsNull() : true,
                $collectionValueType,
            );
        }

        return array_values($metadatas);
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
