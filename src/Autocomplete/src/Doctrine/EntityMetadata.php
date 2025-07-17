<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Doctrine;

use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\Persistence\Mapping\ClassMetadata;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
class EntityMetadata
{
    public function __construct(
        private ClassMetadata $metadata,
    ) {
    }

    public function getAllPropertyNames(): array
    {
        return $this->metadata->getFieldNames();
    }

    public function isAssociation(string $propertyName): bool
    {
        return \array_key_exists($propertyName, $this->metadata->associationMappings)
            || (str_contains($propertyName, '.') && !$this->isEmbeddedClassProperty($propertyName));
    }

    public function isEmbeddedClassProperty(string $propertyName): bool
    {
        $propertyNameParts = explode('.', $propertyName, 2);

        return \array_key_exists($propertyNameParts[0], $this->metadata->embeddedClasses);
    }

    public function getPropertyMetadata(string $propertyName): array
    {
        trigger_deprecation('symfony/ux-autocomplete', '2.15.0', 'Calling EntityMetadata::getPropertyMetadata() is deprecated. You should stop using it, as it will be removed in the future.');

        try {
            return $this->getFieldMetadata($propertyName);
        } catch (\InvalidArgumentException $e) {
            return $this->getAssociationMetadata($propertyName);
        }
    }

    /**
     * @internal
     *
     * @return array<string, mixed>
     */
    public function getFieldMetadata(string $propertyName): array
    {
        if (\array_key_exists($propertyName, $this->metadata->fieldMappings)) {
            // Cast to array, because in doctrine/orm:^3.0; $metadata will be a FieldMapping object
            return (array) $this->metadata->fieldMappings[$propertyName];
        }

        throw new \InvalidArgumentException(\sprintf('The "%s" field does not exist in the "%s" entity.', $propertyName, $this->metadata->getName()));
    }

    /**
     * @internal
     *
     * @return array<string, mixed>
     */
    public function getAssociationMetadata(string $propertyName): array
    {
        if (\array_key_exists($propertyName, $this->metadata->associationMappings)) {
            $associationMapping = $this->metadata->associationMappings[$propertyName];

            // Doctrine ORM 3.0
            if (class_exists(AssociationMapping::class) && $associationMapping instanceof AssociationMapping) {
                return $associationMapping->toArray();
            }

            return $associationMapping;
        }

        throw new \InvalidArgumentException(\sprintf('The "%s" field does not exist in the "%s" entity.', $propertyName, $this->metadata->getName()));
    }

    public function getPropertyDataType(string $propertyName): string
    {
        if (\array_key_exists($propertyName, $this->metadata->fieldMappings)) {
            return $this->getFieldMetadata($propertyName)['type'];
        }

        return $this->getAssociationMetadata($propertyName)['type'];
    }

    public function getIdValue(object $entity): string
    {
        return current($this->metadata->getIdentifierValues($entity));
    }
}
