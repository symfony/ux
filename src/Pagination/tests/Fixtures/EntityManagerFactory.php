<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Tests\Fixtures;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\UX\Pagination\Tests\Fixtures\Entity\Author;

/**
 * Creates in-memory sqlite EntityManagers (ORM 3 / ORM 4 compatible setup).
 */
final class EntityManagerFactory
{
    /**
     * @param list<class-string>                   $entities  Entities to create the schema for
     * @param (callable(Configuration): void)|null $configure Hook to adjust the ORM configuration before the connection is created
     */
    public static function create(array $entities = [Author::class], ?callable $configure = null): EntityManager
    {
        $config = method_exists(ORMSetup::class, 'createAttributeMetadataConfig')
            ? ORMSetup::createAttributeMetadataConfig(
                paths: [__DIR__.'/Entity'],
                isDevMode: true,
            )
            : ORMSetup::createAttributeMetadataConfiguration(
                paths: [__DIR__.'/Entity'],
                isDevMode: true,
            );

        // Enable native lazy objects for PHP 8.4+ with ORM 3 (always on with ORM 4)
        if (\PHP_VERSION_ID >= 80400 && method_exists($config, 'enableNativeLazyObjects')) {
            $config->enableNativeLazyObjects(true);
        }

        if (null !== $configure) {
            $configure($config);
        }

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ], $config);

        $entityManager = new EntityManager($connection, $config);

        new SchemaTool($entityManager)->createSchema(
            array_map($entityManager->getClassMetadata(...), $entities),
        );

        return $entityManager;
    }
}
