<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Doctrine;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;

/**
 * Adapted from EasyCorp/EasyAdminBundle EntityFactory.
 */
class EntityMetadataFactory
{
    public function __construct(
        private DoctrineRegistryWrapper $doctrine,
    ) {
    }

    public function create(?string $entityFqcn): EntityMetadata
    {
        $entityMetadata = $this->getEntityMetadata($entityFqcn);

        return new EntityMetadata($entityMetadata);
    }

    private function getEntityMetadata(string $entityFqcn): ClassMetadata
    {
        $entityManager = $this->getEntityManager($entityFqcn);
        $entityMetadata = $entityManager->getClassMetadata($entityFqcn);

        if (1 !== \count($entityMetadata->getIdentifierFieldNames())) {
            throw new \RuntimeException(\sprintf('Autocomplete does not support Doctrine entities with composite primary keys (such as the ones used in the "%s" entity).', $entityFqcn));
        }

        return $entityMetadata;
    }

    private function getEntityManager(string $entityFqcn): ObjectManager
    {
        if (null === $entityManager = $this->doctrine->getManagerForClass($entityFqcn)) {
            throw new \RuntimeException(\sprintf('There is no Doctrine Entity Manager defined for the "%s" class', $entityFqcn));
        }

        return $entityManager;
    }
}
