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

use Doctrine\Persistence\Mapping\ClassMetadata;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
class EntityMetadata
{
    public function __construct(
        private ClassMetadata $metadata
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
        if (\array_key_exists($propertyName, $this->metadata->fieldMappings)) {
            return $this->metadata->fieldMappings[$propertyName];
        }

        if (\array_key_exists($propertyName, $this->metadata->associationMappings)) {
            return $this->metadata->associationMappings[$propertyName];
        }

        throw new \InvalidArgumentException(sprintf('The "%s" field does not exist in the "%s" entity.', $propertyName, $this->metadata->getName()));
    }

    public function getPropertyDataType(string $propertyName): string
    {
        return $this->getPropertyMetadata($propertyName)['type'];
    }

    public function getIdValue(object $entity): string
    {
        return current($this->metadata->getIdentifierValues($entity));
    }
}
