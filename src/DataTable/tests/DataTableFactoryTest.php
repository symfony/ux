<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\DataTable\DataTableType;
use Symfony\UX\DataTable\Exception\InvalidArgumentException;
use Symfony\UX\DataTable\Query\ArrayQuery;
use Symfony\UX\DataTable\Tests\Fixtures\Entity\Product;
use Symfony\UX\DataTable\Tests\Fixtures\EntityManagerFactory;
use Symfony\UX\DataTable\Tests\Fixtures\ProductDataTableType;

final class DataTableFactoryTest extends TestCase
{
    use DataTableTestTrait;

    public function testCreatesTheDataTableOfAType(): void
    {
        $dataTable = self::createFactory()->create(ProductDataTableType::class, [new Product('Chair')], ['per_page' => 5]);

        self::assertSame('product_data_table', $dataTable->getName());
        self::assertSame(['name', 'price', 'active', 'status'], array_keys($dataTable->getColumns()));
        self::assertSame(['q', 'active'], array_keys($dataTable->getFilters()));
        self::assertSame(5, $dataTable->getOptions()['per_page']);
    }

    public function testCreatesANamedBuilder(): void
    {
        $builder = self::createFactory()->createBuilder(DataTableType::class, [], name: 'products');

        self::assertSame('products', $builder->getName());
        self::assertSame('data_table', self::createFactory()->createBuilder(DataTableType::class, [])->getName());
    }

    public function testAcceptsIterablesQueryBuildersAndQueries(): void
    {
        $factory = self::createFactory();
        $queryBuilder = EntityManagerFactory::create(new Product('Chair'))->createQueryBuilder()->select('p')->from(Product::class, 'p');

        self::assertSame(1, $factory->create(DataTableType::class, new \ArrayIterator([1]))->getPagination()->getTotalItems());
        self::assertSame(1, $factory->create(DataTableType::class, $queryBuilder)->getPagination()->getTotalItems());
        self::assertSame(2, $factory->create(DataTableType::class, new ArrayQuery([1, 2]))->getPagination()->getTotalItems());
    }

    public function testRejectsOtherSources(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A data table source must be an iterable, a Doctrine ORM query builder or a "Symfony\UX\DataTable\Query\QueryInterface", "string" given.');

        self::createFactory()->create(DataTableType::class, 'products');
    }
}
