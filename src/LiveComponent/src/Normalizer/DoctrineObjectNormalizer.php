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
        // $data is the single identifier or array of identifiers
        if (\is_scalar($data) || (\is_array($data) && isset($data[0]))) {
            return $this->objectManagerFor($type)->find($type, $data);
        }

        throw new \LogicException('Invalid denormalization case');
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = [])
    {
        if (
            (\is_scalar($data) || (\is_array($data) && isset($data[0])))
            && null !== $this->objectManagerFor($type)
        ) {
            return true;
        }

        return false;
    }

    public static function getSubscribedServices(): array
    {
        return [
            DenormalizerInterface::class,
        ];
    }

    private function objectManagerFor(string $class): ?ObjectManager
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
