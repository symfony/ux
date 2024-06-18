<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Doctrine;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

/**
 * @author Jason Schilling <jason@sourecode.dev>
 */
class DoctrineClassResolver
{
    private $doctrine;

    public function __construct(?ManagerRegistry $doctrine = null)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @return class-string
     */
    public function resolve(object $entity, ?ObjectManager $em = null): string
    {
        $class = ClassUtil::getEntityClass($entity);

        if (!$this->doctrine) {
            return $class;
        }

        $em = $em ?? $this->doctrine->getManagerForClass($class);

        if (!$em) {
            return $class;
        }

        $classMetadata = $em->getClassMetadata($class);

        if ($classMetadata instanceof ClassMetadata) {
            return $classMetadata->rootEntityName;
        }

        return $class;
    }
}
