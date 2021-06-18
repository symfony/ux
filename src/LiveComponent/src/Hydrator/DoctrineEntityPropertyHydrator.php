<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Hydrator;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Symfony\UX\LiveComponent\Exception\UnsupportedHydrationException;
use Symfony\UX\LiveComponent\PropertyHydratorInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 */
final class DoctrineEntityPropertyHydrator implements PropertyHydratorInterface
{
    /** @var ManagerRegistry[] */
    private $managerRegistries;

    /**
     * @param ManagerRegistry[] $managerRegistries
     */
    public function __construct(iterable $managerRegistries)
    {
        $this->managerRegistries = $managerRegistries;
    }

    public function dehydrate($value)
    {
        if (!\is_object($value)) {
            throw new UnsupportedHydrationException();
        }

        $id = $this
            ->objectManagerFor($class = \get_class($value))
            ->getClassMetadata($class)
            ->getIdentifierValues($value)
        ;

        switch (\count($id)) {
            case 0:
                throw new \RuntimeException("Cannot dehydrate an unpersisted entity ({$class}). If you want to allow this, add a dehydrateWith= option to LiveProp.");
            case 1:
                return array_values($id)[0];
        }

        // composite id
        return $id;
    }

    public function hydrate(string $type, $value)
    {
        return $this->objectManagerFor($type)->find($type, $value);
    }

    private function objectManagerFor(string $class): ObjectManager
    {
        foreach ($this->managerRegistries as $registry) {
            if ($om = $registry->getManagerForClass($class)) {
                return $om;
            }
        }

        throw new UnsupportedHydrationException();
    }
}
