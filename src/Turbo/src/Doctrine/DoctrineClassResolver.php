<?php

namespace Symfony\UX\Turbo\Doctrine;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

class DoctrineClassResolver
{
    private $doctrine;

    public function __construct(?ManagerRegistry $doctrine = null)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param object $entity
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
