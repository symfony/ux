<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Broadcaster;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class IdAccessor
{
    private ?PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        ?PropertyAccessorInterface $propertyAccessor = null,
        private ?ManagerRegistry $doctrine = null,
    ) {
        $this->propertyAccessor = $propertyAccessor ?? (class_exists(PropertyAccess::class) ? PropertyAccess::createPropertyAccessor() : null);
    }

    /**
     * @return array<array-key, mixed>|null
     */
    public function getEntityId(object $entity): ?array
    {
        $entityClass = $entity::class;

        if ($this->doctrine && $em = $this->doctrine->getManagerForClass($entityClass)) {
            return self::getIdentifierValues($em, $entity);
        }

        if ($this->propertyAccessor) {
            return (array) $this->propertyAccessor->getValue($entity, 'id');
        }

        return null;
    }

    /**
     * Same as ClassMetadata::getIdentifierValues(), except that an identifier which is itself
     * an association is replaced by the identifier of the entity it points to: Doctrine hands
     * back that related entity, which callers cannot turn into an identifier string.
     *
     * @internal
     *
     * @return array<string, mixed>
     */
    public static function getIdentifierValues(ObjectManager $em, object $entity): array
    {
        $metadata = $em->getClassMetadata($entity::class);
        $id = $metadata->getIdentifierValues($entity);

        foreach ($id as $field => $value) {
            if ($metadata->hasAssociation($field)) {
                $id[$field] = implode('-', self::getIdentifierValues($em, $value));
            }
        }

        return $id;
    }
}
