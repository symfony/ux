<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Tests\Fixtures;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\UX\DataTable\Tests\Fixtures\Entity\Category;
use Symfony\UX\DataTable\Tests\Fixtures\Entity\Product;

final class EntityManagerFactory
{
    /**
     * Creates an in-memory database holding the given products.
     */
    public static function create(Product ...$products): EntityManager
    {
        $config = ORMSetup::createAttributeMetadataConfig(paths: [__DIR__.'/Entity'], isDevMode: true);
        $config->enableNativeLazyObjects(true);

        $entityManager = new EntityManager(DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config), $config);
        new SchemaTool($entityManager)->createSchema([
            $entityManager->getClassMetadata(Category::class),
            $entityManager->getClassMetadata(Product::class),
        ]);

        foreach ($products as $product) {
            for ($category = $product->category; null !== $category; $category = $category->parent) {
                $entityManager->persist($category);
            }
            $entityManager->persist($product);
        }
        $entityManager->flush();
        $entityManager->clear();

        return $entityManager;
    }
}
