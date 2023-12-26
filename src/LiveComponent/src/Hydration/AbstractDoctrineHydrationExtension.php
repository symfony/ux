<?php

namespace Symfony\UX\LiveComponent\Hydration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

/**
 * @author Jean-Paul van der Wegen <info@jpvdw.nl>
 *
 * @experimental
 *
 * @internal
 */
abstract class AbstractDoctrineHydrationExtension
{
    /**
     * @param ManagerRegistry[] $managerRegistries
     */
    public function __construct(
        private iterable $managerRegistries,
    ) {
    }

    protected function objectManagerFor(string $class): ?ObjectManager
    {
        if (!class_exists($class)) {
            return null;
        }

        // todo cache/warmup an array of classes that are "doctrine objects"
        foreach ($this->managerRegistries as $registry) {
            if ($om = $registry->getManagerForClass($class)) {
                return self::ensureManagedObject($om, $class);
            }
        }

        return null;
    }

    /**
     * Ensure the $class is not embedded or a mapped superclass.
     */
    private static function ensureManagedObject(ObjectManager $om, string $class): ?ObjectManager
    {
        if (!$om instanceof EntityManagerInterface) {
            // todo might need to add some checks once ODM support is added
            return $om;
        }

        $metadata = $om->getClassMetadata($class);

        if ($metadata->isEmbeddedClass || $metadata->isMappedSuperclass) {
            return null;
        }

        return $om;
    }

    /**
     * @psalm-param class-string<T> $className
     *
     * @return object|null
     *
     * @psalm-return T|null
     *
     * @template T of object
     */
    protected function findObject(string $className, mixed $id)
    {
        return $this->objectManagerFor($className)->find($className, $id);
    }

    protected function getIdentifierValue(object $object): mixed
    {
        $id = $this
            ->objectManagerFor($class = $object::class)
            ->getClassMetadata($class)
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
