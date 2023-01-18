<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Normalizer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\UX\LiveComponent\LiveComponentHydrator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 *
 * @internal
 */
final class DoctrineObjectNormalizer implements NormalizerInterface, DenormalizerInterface, ServiceSubscriberInterface
{
    /** Flag to avoid recursion in the normalizer */
    private const DOCTRINE_OBJECT_ALREADY_NORMALIZED = 'doctrine_object_normalizer.normalized';

    /**
     * @param ManagerRegistry[] $managerRegistries
     */
    public function __construct(
        private iterable $managerRegistries,
        private ContainerInterface $container,
    ) {
    }

    /**
     * @param object $object
     */
    public function normalize(mixed $object, string $format = null, array $context = []): mixed
    {
        $id = $this
            ->objectManagerFor($class = \get_class($object))
            ->getClassMetadata($class)
            ->getIdentifierValues($object)
        ;

        switch (\count($id)) {
            case 0:
                // this case shouldn't happen thanks to supportsNormalization()
                throw new \RuntimeException("Cannot dehydrate an unpersisted entity ({$class}). If you want to allow this, add a dehydrateWith= option to LiveProp.");
            case 1:
                return array_values($id)[0];
        }

        // composite id
        return $id;
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        if (true !== ($context[LiveComponentHydrator::LIVE_CONTEXT] ?? null) || !\is_object($data)) {
            return false;
        }

        // not an entity?
        if (null === $this->objectManagerFor($data::class)) {
            return false;
        }

        $identifiers = $this->getIdentifierValues($data);

        // this is persisted entity: normalize it
        if (0 !== \count($identifiers)) {
            return true;
        }

        // this is a non-persisted entity: allow it to normalize using the default normalizer
        return false;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): ?object
    {
        if (null === $data) {
            return null;
        }

        // $data is the single identifier or array of identifiers
        if (\is_scalar($data) || (\is_array($data) && isset($data[0]))) {
            return $this->objectManagerFor($type)->find($type, $data);
        }

        // $data is an associative array to denormalize the entity
        // allow the object to be denormalized using the default denormalizer
        // except that the denormalizer has problems with "nullable: false" columns
        // https://github.com/symfony/symfony/issues/49149
        // so, we send the object through the denormalizer, but turn type-checks off
        // NOTE: The hydration system will already have prevented a writable property
        //       from reaching this.
        $context[AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT] = true;
        $context[self::DOCTRINE_OBJECT_ALREADY_NORMALIZED] = true;

        return $this->getDenormalizer()->denormalize($data, $type, $format, $context);
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = [])
    {
        if (true !== ($context[LiveComponentHydrator::LIVE_CONTEXT] ?? null) || !class_exists($type)) {
            return false;
        }

        // not an entity?
        if (null === $this->objectManagerFor($type)) {
            return false;
        }

        // avoid recursion
        if ($context[self::DOCTRINE_OBJECT_ALREADY_NORMALIZED] ?? false) {
            return false;
        }

        return true;
    }

    public static function getSubscribedServices(): array
    {
        return [
            DenormalizerInterface::class,
        ];
    }

    private function objectManagerFor(string $class): ?ObjectManager
    {
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

    private function getIdentifierValues(object $object): array
    {
        return $this
            ->objectManagerFor($class = \get_class($object))
            ->getClassMetadata($class)
            ->getIdentifierValues($object)
        ;
    }

    private function getDenormalizer(): DenormalizerInterface
    {
        return $this->container->get(DenormalizerInterface::class);
    }
}
