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
     * @return string[]|null
     */
    public function getEntityId(object $entity): ?array
    {
        $entityClass = $entity::class;

        if ($this->doctrine && $em = $this->doctrine->getManagerForClass($entityClass)) {
            return $em->getClassMetadata($entityClass)->getIdentifierValues($entity);
        }

        if ($this->propertyAccessor) {
            return (array) $this->propertyAccessor->getValue($entity, 'id');
        }

        return null;
    }
}
