<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Tests\Integration\Doctrine;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Autocomplete\Doctrine\EntitySearchUtil;
use Symfony\UX\Autocomplete\Tests\Fixtures\Entity\Product;
use Symfony\UX\Autocomplete\Tests\Fixtures\Factory\CategoryFactory;
use Symfony\UX\Autocomplete\Tests\Fixtures\Factory\ProductFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class EntitySearchUtilTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testItCreatesBasicStringSearchQuery(): void
    {
        $prod1 = ProductFactory::createOne(['name' => 'bar prod1']);
        $prod2 = ProductFactory::createOne(['name' => 'foo prod2']);
        ProductFactory::createOne(['name' => 'baz thing3']);
        $prod4 = ProductFactory::createOne(['description' => 'all about prod 4']);

        $results = $this->callAddSearchClass('prod');
        $this->assertSame([$prod1->object(), $prod2->object(), $prod4->object()], $results);
    }

    public function testItSearchesOnCorrectFields(): void
    {
        $prod1 = ProductFactory::createOne(['name' => 'bar prod1']);
        ProductFactory::createOne(['description' => 'foo prod2']);

        $results = $this->callAddSearchClass('prod', ['name']);
        $this->assertSame([$prod1->object()], $results);
    }

    public function testItCanSearchOnRelationFields(): void
    {
        $category1 = CategoryFactory::createOne(['name' => 'foods']);
        $category2 = CategoryFactory::createOne(['name' => 'toys']);
        $prod1 = ProductFactory::createOne(['name' => 'pizza', 'category' => $category1]);
        $prod2 = ProductFactory::createOne(['name' => 'toy food', 'category' => $category2]);
        ProductFactory::createOne(['name' => 'puzzle', 'category' => $category2]);

        $results = $this->callAddSearchClass('food', ['name', 'category.name']);
        $this->assertSame([$prod1->object(), $prod2->object()], $results);
    }

    /**
     * @return array<Product>
     */
    private function callAddSearchClass(string $search, ?array $searchableProperties = null): array
    {
        /** @var ManagerRegistry $registry */
        $registry = self::getContainer()->get('doctrine');
        /** @var EntityRepository $repository */
        $repository = $registry->getRepository(Product::class);
        $queryBuilder = $repository->createQueryBuilder('prod');

        /** @var EntitySearchUtil $searchUtil */
        $searchUtil = self::getContainer()->get('ux.autocomplete.entity_search_util');
        $searchUtil->addSearchClause(
            $queryBuilder,
            $search,
            Product::class,
            $searchableProperties
        );

        return $queryBuilder->getQuery()->execute();
    }
}
