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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * Small wrapper around ManagerRegistry to help if Doctrine is missing.
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
class DoctrineRegistryWrapper
{
    public function __construct(
        private ?ManagerRegistry $registry = null,
    ) {
    }

    public function getRepository(string $class): EntityRepository
    {
        return $this->getRegistry()->getRepository($class);
    }

    public function getManagerForClass(string $class): EntityManagerInterface
    {
        return $this->getRegistry()->getManagerForClass($class);
    }

    private function getRegistry(): ManagerRegistry
    {
        if (null === $this->registry) {
            throw new \LogicException('Doctrine must be installed to use the entity features of AutocompleteBundle.');
        }

        return $this->registry;
    }
}
