<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Hydration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ObjectManager;

trait DoctrineHydrationTrait
{
    private function objectManagerFor(string $className): ?ObjectManager
    {
        if (!class_exists($className)) {
            return null;
        }

        // todo cache/warmup an array of classes that are "doctrine objects"
        foreach ($this->managerRegistries as $registry) {
            if ($om = $registry->getManagerForClass($className)) {
                return self::ensureManagedObject($om, $className);
            }
        }

        return null;
    }

    /**
     * Ensure the $class is not embedded or a mapped superclass.
     */
    private static function ensureManagedObject(ObjectManager $om, string $className): ?ObjectManager
    {
        if (!$om instanceof EntityManagerInterface) {
            // todo might need to add some checks once ODM support is added
            return $om;
        }

        $metadata = $om->getClassMetadata($className);

        if ($metadata->isEmbeddedClass || $metadata->isMappedSuperclass) {
            return null;
        }

        return $om;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @throws EntityNotFoundException
     */
    protected function getObject(string $className, mixed $id): object
    {
        $object = $this->objectManagerFor($className)->find($className, $id);
        if (!$object instanceof $className) {
            throw new EntityNotFoundException(sprintf('Cannot find entity "%s" with id "%s".', $className, $id));
        }

        return $object;
    }

    private function getIdentifierValue(object $object): mixed
    {
        $id = $this
            ->objectManagerFor($className = $object::class)
            ->getClassMetadata($className)
            ->getIdentifierValues($object)
        ;

        switch (\count($id)) {
            case 0:
                // a non-persisted entity
                return [];
            case 1:
                return array_values($id)[0];
        }

        // composite id
        return $id;
    }
}
