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
use Symfony\UX\Turbo\Doctrine\DoctrineIdAccessor;

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
     * @return array<string, array<string, string>>|array<string, string>|null
     */
    public function getEntityId(object $entity): ?array
    {
        if (null !== ($id = $this->doctrineIdAccessor->getEntityId($entity))) {
            return $id;
        }

        if ($this->propertyAccessor) {
            return (array) $this->propertyAccessor->getValue($entity, 'id');
        }

        return null;
    }
}
