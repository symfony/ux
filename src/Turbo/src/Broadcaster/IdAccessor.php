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

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class IdAccessor
{
    private $propertyAccessor;
    private $doctrineIdAccessor;

    public function __construct(?PropertyAccessorInterface $propertyAccessor = null, ?DoctrineIdAccessor $doctrineIdAccessor = null)
    {
        $this->propertyAccessor = $propertyAccessor ?? (class_exists(PropertyAccess::class) ? PropertyAccess::createPropertyAccessor() : null);
        $this->doctrineIdAccessor = $doctrineIdAccessor ?? new DoctrineIdAccessor();
    }

    /**
     * @return string[]
     */
    public function getEntityId(object $entity): ?array
    {
        $entityClass = $entity::class;

        if (null !== ($id = $this->doctrineIdAccessor->getEntityId($entity))) {
            return $id;
        }

        if ($this->propertyAccessor) {
            return (array) $this->propertyAccessor->getValue($entity, 'id');
        }

        return null;
    }
}
