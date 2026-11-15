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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\DataTable\Query\ArrayQuery;
use Symfony\UX\DataTable\Query\Operator;
use Symfony\UX\DataTable\Tests\Fixtures\Entity\Product;
use Symfony\UX\DataTable\Tests\Fixtures\Size;
use Symfony\UX\DataTable\Tests\Fixtures\Status;

final class ArrayQueryTest extends TestCase
{
    private const array ROWS = [
        ['name' => 'Chair', 'price' => 10],
        ['name' => 'Armchair', 'price' => 30],
        ['name' => 'Table', 'price' => 20],
    ];

    /**
     * @return iterable<array{Operator, mixed, list<string>}>
     */
    public static function provideOperators(): iterable
    {
        yield [Operator::Equals, 20, ['Table']];
        yield [Operator::NotEquals, 20, ['Chair', 'Armchair']];
        yield [Operator::GreaterThan, 20, ['Armchair']];
        yield [Operator::GreaterThanOrEqual, 20, ['Armchair', 'Table']];
        yield [Operator::LessThan, 20, ['Chair']];
        yield [Operator::LessThanOrEqual, 20, ['Chair', 'Table']];
        yield [Operator::In, [10, 20], ['Chair', 'Table']];
        yield [Operator::In, new \ArrayIterator([30]), ['Armchair']];
        yield [Operator::In, 30, ['Armchair']];
    }

    #[DataProvider('provideOperators')]
    public function testFiltersWithTheOperator(Operator $operator, mixed $value, array $expected): void
    {
        $query = new ArrayQuery(self::ROWS);
        $query->filter('price', $operator, $value);

        self::assertSame($expected, array_column($query->getSource(), 'name'));
    }

    public function testFiltersStringsCaseInsensitively(): void
    {
        foreach ([[Operator::Contains, 'CHAIR', ['Chair', 'Armchair']], [Operator::StartsWith, 'ch', ['Chair']], [Operator::EndsWith, 'BLE', ['Table']]] as [$operator, $value, $expected]) {
            $query = new ArrayQuery(self::ROWS);
            $query->filter('name', $operator, $value);

            self::assertSame($expected, array_column($query->getSource(), 'name'));
        }
    }

    public function testFiltersNonStringValuesAsEmptyStrings(): void
    {
        $query = new ArrayQuery([['name' => ['nested']], ['name' => new \ArrayObject()]]);
        $query->filter('name', Operator::Contains, '');

        self::assertCount(2, $query->getSource());
    }

    public function testSearchesSeveralPaths(): void
    {
        $query = new ArrayQuery([...self::ROWS, ['name' => 'Lamp', 'price' => 'chair-compatible']]);
        $query->search(['name', 'price'], 'chair');

        self::assertSame(['Chair', 'Armchair', 'Lamp'], array_column($query->getSource(), 'name'));
    }

    public function testSortsByEachPathInTurn(): void
    {
        $query = new ArrayQuery([...self::ROWS, ['name' => 'Bench', 'price' => 20]]);
        $query->sort('price', \SortDirection::Descending);
        $query->sort('name', \SortDirection::Ascending);

        self::assertSame(['Armchair', 'Bench', 'Table', 'Chair'], array_column($query->getSource(), 'name'));
    }

    public function testAcceptsObjectsAndIterables(): void
    {
        $query = new ArrayQuery((static function () {
            yield 'a' => new Product('Table');
            yield 'b' => new Product('Chair');
        })());
        $query->sort('name', \SortDirection::Ascending);

        self::assertSame(['Chair', 'Table'], array_map(static fn (Product $product) => $product->name, $query->getSource()));
    }

    public function testScalarRowsHaveNoValues(): void
    {
        $query = new ArrayQuery(['Chair', 'Table']);
        $query->sort('name', \SortDirection::Descending);
        self::assertSame(['Chair', 'Table'], $query->getSource());

        $query->filter('name', Operator::NotEquals, 'Chair');
        self::assertSame([], $query->getSource());
    }

    public function testNullValuesMatchNoCondition(): void
    {
        $rows = [['active' => null, 'price' => null], ['active' => false, 'price' => 10], ['active' => true, 'price' => 30]];

        foreach ([['active', Operator::Equals, false, [1]], ['active', Operator::NotEquals, true, [1]], ['price', Operator::LessThan, 20, [1]], ['price', Operator::In, [0, 10], [1]]] as [$path, $operator, $value, $expected]) {
            $query = new ArrayQuery($rows);
            $query->filter($path, $operator, $value);

            self::assertSame(array_map(static fn (int $index): array => $rows[$index], $expected), $query->getSource());
        }
    }

    public function testComparesBackedEnumsByValue(): void
    {
        $rows = [['status' => 'draft'], ['status' => 'published'], ['status' => Status::Published]];

        $query = new ArrayQuery($rows);
        $query->filter('status', Operator::Equals, Status::Published);
        self::assertSame([$rows[1], $rows[2]], $query->getSource());

        $query = new ArrayQuery($rows);
        $query->filter('status', Operator::In, [Status::Draft]);
        self::assertSame([$rows[0]], $query->getSource());
    }

    public function testKeepsTheRowsOfRepeatedKeys(): void
    {
        $query = new ArrayQuery((static function () {
            yield 'chairs' => 'Chair';
            yield 'chairs' => 'Armchair';
        })());

        self::assertSame(['Chair', 'Armchair'], $query->getSource());
    }

    public function testSortsEnumsAndStringsLikeADatabase(): void
    {
        $query = new ArrayQuery([['status' => Status::Published], ['status' => Size::Small], ['status' => Status::Draft], ['status' => Size::Large]]);
        $query->sort('status', \SortDirection::Ascending);
        self::assertSame([Status::Draft, Size::Large, Status::Published, Size::Small], array_column($query->getSource(), 'status'));

        $query = new ArrayQuery([['name' => 'carl'], ['name' => 'Bob'], ['name' => 'alice']]);
        $query->sort('name', \SortDirection::Ascending);
        self::assertSame(['alice', 'Bob', 'carl'], array_column($query->getSource(), 'name'));
    }
}
