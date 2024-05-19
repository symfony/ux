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
use Symfony\UX\Turbo\Doctrine\ClassUtil;

/**
 * @author Jason Schilling <jason@sourecode.dev>
 */
class DoctrineIdAccessor
{
    private $doctrine;

    public function __construct(?ManagerRegistry $doctrine = null)
    {
        $this->doctrine = $doctrine;
    }

    public function getEntityId(object $entity): ?array
    {
        $entityClass = $entity::class;

        if ($this->doctrine && $em = $this->doctrine->getManagerForClass($entityClass)) {
            return $this->getIdentifierValues($em, $entity);
        }

        return null;
    }

    private function getIdentifierValues(ObjectManager $em, object $entity): array
    {
        $class = ClassUtil::getEntityClass($entity);

        $values = $em->getClassMetadata($class)->getIdentifierValues($entity);

        foreach ($values as $key => $value) {
            if (\is_object($value)) {
                $values[$key] = $this->getIdentifierValues($em, $value);
            }
        }

        return $values;
    }
}
