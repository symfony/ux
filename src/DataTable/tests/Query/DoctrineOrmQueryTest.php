<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Tests\Query;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\DataTable\Exception\InvalidArgumentException;
use Symfony\UX\DataTable\Query\DoctrineOrmQuery;
use Symfony\UX\DataTable\Query\Operator;
use Symfony\UX\DataTable\Tests\Fixtures\Entity\Category;
use Symfony\UX\DataTable\Tests\Fixtures\Entity\Dimensions;
use Symfony\UX\DataTable\Tests\Fixtures\Entity\Product;
use Symfony\UX\DataTable\Tests\Fixtures\EntityManagerFactory;
use Symfony\UX\DataTable\Tests\Fixtures\Status;

final class DoctrineOrmQueryTest extends TestCase
{
    private EntityManager $entityManager;

    protected function setUp(): void
    {
        $furniture = new Category('Furniture', new Category('Home'));
        $this->entityManager = EntityManagerFactory::create(
            new Product('Chair', 10, category: $furniture, dimensions: new Dimensions(40)),
            new Product('Armchair 100%', 30, status: Status::Published, category: $furniture, dimensions: new Dimensions(80)),
            new Product('Table_1', 20, active: false, category: new Category('Tables', new Category('Office')), dimensions: new Dimensions(120)),
            new Product('Lamp', 20),
        );
    }

    /**
     * @return iterable<array{string, Operator, mixed, list<string>}>
     */
    public static function provideOperators(): iterable
    {
        yield ['price', Operator::Equals, 20, ['Table_1', 'Lamp']];
        yield ['price', Operator::NotEquals, 20, ['Chair', 'Armchair 100%']];
        yield ['price', Operator::GreaterThan, 20, ['Armchair 100%']];
        yield ['price', Operator::GreaterThanOrEqual, 20, ['Armchair 100%', 'Table_1', 'Lamp']];
        yield ['price', Operator::LessThan, 20, ['Chair']];
        yield ['price', Operator::LessThanOrEqual, 20, ['Chair', 'Table_1', 'Lamp']];
        yield ['price', Operator::In, [10, 30], ['Chair', 'Armchair 100%']];
        yield ['price', Operator::In, new \ArrayIterator([30]), ['Armchair 100%']];
        yield ['status', Operator::In, Status::Published, ['Armchair 100%']];
        yield ['active', Operator::Equals, false, ['Table_1']];
        yield ['name', Operator::Contains, 'CHAIR', ['Chair', 'Armchair 100%']];
        yield ['name', Operator::Contains, '%', ['Armchair 100%']];
        yield ['name', Operator::StartsWith, 'table_', ['Table_1']];
        yield ['name', Operator::StartsWith, '_', []];
        yield ['name', Operator::EndsWith, 'MP', ['Lamp']];
        yield ['name', Operator::Contains, '!', []];
        yield ['name', Operator::Contains, new \ArrayObject(), ['Chair', 'Armchair 100%', 'Table_1', 'Lamp']];
    }

    #[DataProvider('provideOperators')]
    public function testFiltersWithTheOperator(string $path, Operator $operator, mixed $value, array $expected): void
    {
        $query = new DoctrineOrmQuery($this->queryBuilder());
        $query->filter($path, $operator, $value);

        self::assertSame($expected, $this->names($query->getSource()));
    }

    public function testJoinsToOneAssociations(): void
    {
        $query = new DoctrineOrmQuery($this->queryBuilder());
        $query->filter('category.name', Operator::Equals, 'Tables');
        $query->sort('category.name', \SortDirection::Ascending);
        self::assertSame(['Table_1'], $this->names($query->getSource()));

        $query = new DoctrineOrmQuery($this->queryBuilder());
        $query->filter('category.parent.name', Operator::Equals, 'Home');
        $query->sort('category.parent.name', \SortDirection::Descending);
        $query->sort('category.name', \SortDirection::Ascending);
        self::assertSame(['Chair', 'Armchair 100%'], $this->names($query->getSource()));
        self::assertCount(2, $query->getSource()->getDQLPart('join')['p']);
    }

    public function testReadsEmbeddables(): void
    {
        $query = new DoctrineOrmQuery($this->queryBuilder());
        $query->filter('dimensions.width', Operator::GreaterThan, 50);
        $query->sort('dimensions.width', \SortDirection::Descending);

        self::assertSame(['Table_1', 'Armchair 100%'], $this->names($query->getSource()));
    }

    public function testRejectsToManyAssociations(): void
    {
        $query = new DoctrineOrmQuery($this->entityManager->createQueryBuilder()->select('c')->from(Category::class, 'c'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The path "products.name" goes through the to-many association "products", which would duplicate the rows: join it in the query builder and use its alias.');

        $query->filter('products.name', Operator::Equals, 'Chair');
    }

    public function testUsesKnownAliases(): void
    {
        $query = new DoctrineOrmQuery($this->queryBuilder()->innerJoin('p.category', 'c'));
        $query->filter('c.name', Operator::Equals, 'Furniture');

        self::assertSame(['Chair', 'Armchair 100%'], $this->names($query->getSource()));
        self::assertCount(1, $query->getSource()->getDQLPart('join')['p']);
    }

    public function testSearchesSeveralPaths(): void
    {
        $query = new DoctrineOrmQuery($this->queryBuilder());
        $query->search(['name', 'category.name'], 'TABLE');

        self::assertSame(['Table_1'], $this->names($query->getSource()));

        $query = new DoctrineOrmQuery($this->queryBuilder());
        $query->search(['name', 'category.name'], 'furn');

        self::assertSame(['Chair', 'Armchair 100%'], $this->names($query->getSource()));
    }

    public function testSortsBeforeTheInitialOrder(): void
    {
        $query = new DoctrineOrmQuery($this->queryBuilder()->orderBy('p.name', 'DESC'));
        $query->sort('price', \SortDirection::Ascending);

        self::assertSame(['Chair', 'Table_1', 'Lamp', 'Armchair 100%'], $this->names($query->getSource()));
    }

    public function testKeepsTheInitialOrderWithoutSort(): void
    {
        $query = new DoctrineOrmQuery($this->queryBuilder()->orderBy('p.name', 'DESC'));

        self::assertSame(['Table_1', 'Lamp', 'Chair', 'Armchair 100%'], $this->names($query->getSource()));
    }

    public function testDoesNotModifyTheGivenQueryBuilder(): void
    {
        $queryBuilder = $this->queryBuilder();
        $query = new DoctrineOrmQuery($queryBuilder);
        $query->filter('name', Operator::Equals, 'Chair');
        $query->sort('name', \SortDirection::Ascending);

        self::assertCount(4, $queryBuilder->getQuery()->getResult());
        self::assertNotSame($query->getSource(), $query->getSource());
    }

    public function testClonesItsQueryBuilder(): void
    {
        $query = new DoctrineOrmQuery($this->queryBuilder());
        $clone = clone $query;
        $clone->filter('price', Operator::Equals, 20);

        self::assertSame(['Chair', 'Armchair 100%', 'Table_1', 'Lamp'], $this->names($query->getSource()));
        self::assertSame(['Table_1', 'Lamp'], $this->names($clone->getSource()));
    }

    private function queryBuilder(): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()->select('p')->from(Product::class, 'p')->addOrderBy('p.id');
    }

    /**
     * @return list<string>
     */
    private function names(QueryBuilder $queryBuilder): array
    {
        return array_map(static fn (Product $product): string => $product->name, $queryBuilder->getQuery()->getResult());
    }
}
