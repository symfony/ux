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
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\MappingException;
use Doctrine\Persistence\ObjectManager;

/**
 * Handles hydration of Doctrine entities.
 *
 * @internal
 */
class DoctrineEntityHydrationExtension implements HydrationExtensionInterface
{
    /**
     * @param ManagerRegistry[] $managerRegistries
     */
    public function __construct(
        private iterable $managerRegistries,
    ) {
    }

    public function supports(string $className): bool
    {
        return null !== $this->objectManagerFor($className);
    }

    public function hydrate(mixed $value, string $className): ?object
    {
        // an empty array means a non-persisted entity
        // we support instantiating with no constructor args
        if (\is_array($value) && 0 === \count($value)) {
            return new $className();
        }

        // e.g. an empty string
        if (!$value) {
            return null;
        }

        // $data is a single identifier or array of identifiers
        if (\is_scalar($value) || \is_array($value)) {
            return $this->objectManagerFor($className)->find($className, $value);
        }

        throw new \InvalidArgumentException(\sprintf('Cannot hydrate Doctrine entity "%s". Value of type "%s" is not supported.', $className, get_debug_type($value)));
    }

    public function dehydrate(object $object): mixed
    {
        $id = $this
            ->objectManagerFor($class = $object::class)
            ->getClassMetadata($class)
            ->getIdentifierValues($object);

        // Dehydrate ID values in case they are other entities
        $id = array_map(fn ($id) => \is_object($id) && $this->supports($id::class) ? $this->dehydrate($id) : $id, $id);

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

    private function objectManagerFor(string $class): ?ObjectManager
    {
        if (class_exists($class)) {
            // Keep the standard way for a class
            // todo cache/warmup an array of classes that are "doctrine objects"
            foreach ($this->managerRegistries as $registry) {
                if ($om = $registry->getManagerForClass($class)) {
                    return self::ensureManagedObject($om, $class);
                }
            }

            return null;
        }

        if (interface_exists($class)) {
            // special handler for interfaces
            // For use cases : @see https://symfony.com/doc/current/doctrine/resolve_target_entity.html
            // As today, getManagerForClass don't resolve nicely aliased interfaces
            // The workaround is to enum over each object manager and trying to get metadata
            // The metadata are indeed, resolved nicely
            // Fore more details :
            // @see \Doctrine\ORM\Tools\ResolveTargetEntityListener

            // todo cache/warmup an array of interfaces that are resolved "doctrine objects"
            foreach ($this->managerRegistries as $registry) {
                foreach ($registry->getManagers() as $om) {
                    // the getClassMetaData can indeed throw an exception
                    // so, we
                    try {
                        if (null !== $om->getClassMetadata($class)) {
                            return self::ensureManagedObject($om, $class);
                        }
                    } catch (MappingException $e) {
                        // I did not find a nice way to check if it is because the class is really unknown
                        // It is good to check for a specific exception ?
                        // eg: \Doctrine\Persistence\Mapping\MappingException
                        // @see \Doctrine\Persistence\Mapping\AbstractClassMetadataFactory::getMetadataFor
                        // throw $e;
                    }
                }
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
}
