<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Tests\Adapter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Table;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Pagination\Adapter\DoctrineDbalAdapter;
use Symfony\UX\Pagination\Cursor\CursorBoundary;
use Symfony\UX\Pagination\Cursor\CursorOrder;
use Symfony\UX\Pagination\Exception\InvalidArgumentException;
use Symfony\UX\Pagination\Exception\RuntimeException;

#[CoversClass(DoctrineDbalAdapter::class)]
final class DoctrineDbalAdapterTest extends TestCase
{
    private Connection $connection;
    private DoctrineDbalAdapter $adapter;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);
        $this->adapter = new DoctrineDbalAdapter();

        $table = new Table('items');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('category', 'string', ['length' => 32]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $this->connection->createSchemaManager()->createTable($table);

        for ($id = 1; $id <= 25; ++$id) {
            $this->connection->insert('items', [
                'category' => 0 === $id % 2 ? 'even' : 'odd',
                'name' => 'Item '.$id,
            ]);
        }
    }

    protected function tearDown(): void
    {
        $this->connection->close();
    }

    public function testSupportsDbalQueryBuilder()
    {
        self::assertTrue($this->adapter->supports($this->query()));
        self::assertFalse($this->adapter->supports([]));
    }

    public function testSlicesWithoutMutatingTheSource()
    {
        $source = $this->query()->orderBy('id', 'ASC');

        $items = $this->adapter->slice($source, 10, 5);

        self::assertSame([11, 12, 13, 14, 15], array_column($items, 'id'));
        self::assertCount(25, $source->executeQuery()->fetchAllAssociative());
    }

    public function testCountsAFilteredQueryAndIgnoresOrderingAndLimits()
    {
        $source = $this->query()
            ->where('category = :category')
            ->setParameter('category', 'even')
            ->orderBy('id', 'DESC')
            ->setFirstResult(2)
            ->setMaxResults(3);

        self::assertSame(12, $this->adapter->count($source));
    }

    public function testCursorContextFingerprintsSqlParametersTypesAndApplicationContext()
    {
        $source = $this->query()
            ->where('category = :category')
            ->andWhere('id > :filters')
            ->setParameter('category', 'odd', ParameterType::STRING)
            ->setParameter('filters', [
                'date' => new \DateTimeImmutable('2026-07-28 12:34:56.123456+02:00'),
                'backed' => DbalBackedContext::Books,
                'unit' => DbalUnitContext::Catalog,
                'scalar' => 10,
                'null' => null,
            ]);

        $context = json_decode($this->adapter->getCursorContext($source, 'tenant-a'), true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame(DoctrineDbalAdapter::class, $context['adapter']);
        self::assertSame('tenant-a', $context['context']);
        self::assertSame(['category', 'filters'], array_keys($context['parameters']));
        self::assertSame(ParameterType::STRING->name, $context['parameters']['category']['type']);
        self::assertSame('odd', $context['parameters']['category']['value']);
        self::assertSame('2026-07-28 12:34:56.123456+02:00', $context['parameters']['filters']['value']['date']['value']);
        self::assertSame('books', $context['parameters']['filters']['value']['backed']['value']);
        self::assertSame('Catalog', $context['parameters']['filters']['value']['unit']['name']);

        self::assertNotSame(
            $this->adapter->getCursorContext($source, 'tenant-a'),
            $this->adapter->getCursorContext($source, 'tenant-b'),
        );
    }

    public function testCursorContextRejectsUnsupportedSources()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->adapter->getCursorContext([], null);
    }

    public function testCursorContextRejectsUnstableParameterValues()
    {
        $source = $this->query()->setParameter('invalid', new \stdClass());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot derive a stable cursor context');
        $this->adapter->getCursorContext($source, null);
    }

    public function testSlicesWithLookahead()
    {
        [$items, $hasMore] = $this->adapter->sliceWithLookahead($this->query()->orderBy('id', 'ASC'), 10, 10);

        self::assertSame(range(11, 20), array_column($items, 'id'));
        self::assertTrue($hasMore);
    }

    public function testCursorPaginatesForwardAndBackward()
    {
        $source = $this->query();
        $order = CursorOrder::byFields(['id'], 'ASC');

        $first = $this->adapter->sliceWithCursor($source, null, 10, $order);
        self::assertSame(range(1, 10), array_column($first->items, 'id'));
        self::assertNotNull($first->next);
        self::assertNull($first->previous);

        $second = $this->adapter->sliceWithCursor($source, $first->next, 10, $order);
        self::assertSame(range(11, 20), array_column($second->items, 'id'));
        self::assertNotNull($second->next);
        self::assertNotNull($second->previous);

        $back = $this->adapter->sliceWithCursor($source, $second->previous, 10, $order);
        self::assertSame(range(1, 10), array_column($back->items, 'id'));
        self::assertNull($back->previous);
        self::assertNotNull($back->next);
    }

    public function testCursorDoesNotOverwriteApplicationParameters()
    {
        $source = $this->query()
            ->where('id > :ux_pagination_cursor_0')
            ->setParameter('ux_pagination_cursor_0', 8);

        $result = $this->adapter->sliceWithCursor(
            $source,
            new CursorBoundary([3]),
            2,
            CursorOrder::byFields(['id'], 'ASC'),
        );

        self::assertSame([9, 10], array_column($result->items, 'id'));
    }

    public function testCursorBackwardFromThirdPageKeepsBothDirections()
    {
        $source = $this->query();
        $order = CursorOrder::byFields(['id'], 'ASC');
        $first = $this->adapter->sliceWithCursor($source, null, 10, $order);
        $second = $this->adapter->sliceWithCursor($source, $first->next, 10, $order);
        $third = $this->adapter->sliceWithCursor($source, $second->next, 10, $order);
        $back = $this->adapter->sliceWithCursor($source, $third->previous, 10, $order);

        self::assertSame(range(11, 20), array_column($back->items, 'id'));
        self::assertNotNull($back->previous);
        self::assertNotNull($back->next);
    }

    public function testCursorBackwardBeforeFirstItemReturnsAnEmptySlice()
    {
        $result = $this->adapter->sliceWithCursor(
            $this->query(),
            new CursorBoundary([1], false),
            10,
            CursorOrder::byFields(['id'], 'ASC'),
        );

        self::assertSame([], $result->items);
        self::assertNull($result->previous);
        self::assertNull($result->next);
        self::assertFalse($result->hasNext);
    }

    public function testCursorSupportsCompositeQualifiedFields()
    {
        $source = $this->connection->createQueryBuilder()
            ->select('i.id', 'i.category', 'i.name')
            ->from('items', 'i');
        $order = CursorOrder::byFields(['i.category', 'i.id'], 'ASC');

        $first = $this->adapter->sliceWithCursor($source, null, 5, $order);
        self::assertCount(5, $first->items);
        self::assertNotNull($first->next);

        $second = $this->adapter->sliceWithCursor($source, $first->next, 5, $order);
        self::assertCount(5, $second->items);
        self::assertNotSame(array_column($first->items, 'id'), array_column($second->items, 'id'));
    }

    public function testCursorRejectsUnsafeFieldNamesAndExistingOrder()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid DBAL cursor field');

        $this->adapter->resolveCursorOrder($this->query(), ['id DESC; DELETE'], 'ASC');
    }

    public function testCursorRejectsInvalidDirection()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('direction must be "ASC" or "DESC"');
        $this->adapter->resolveCursorOrder($this->query(), ['id'], 'sideways');
    }

    public function testCursorRejectsAnEmptyFieldList()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one cursor field');
        $this->adapter->resolveCursorOrder($this->query(), [], 'ASC');
    }

    public function testCursorRejectsANonStringField()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be non-empty strings');

        $this->adapter->resolveCursorFields($this->query(), [42]); // @phpstan-ignore argument.type
    }

    public function testCursorOrderMustBeExplicit()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('requires an explicit orderBy()');

        $this->adapter->resolveCursorOrder($this->query(), null, null);
    }

    public function testCursorRejectsAnExistingOrder()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('owns ORDER BY');
        $this->adapter->sliceWithCursor($this->query()->orderBy('id'), null, 10, CursorOrder::byFields(['id'], 'ASC'));
    }

    public function testCursorRejectsMismatchedBoundary()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cursor values count does not match');

        $this->adapter->sliceWithCursor($this->query(), new CursorBoundary([1]), 10, CursorOrder::byFields(['category', 'id'], 'ASC'));
    }

    public function testCursorRejectsAnOpaqueOrder()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('requires a field-based cursor order');

        $this->adapter->sliceWithCursor($this->query(), null, 10, CursorOrder::byIdentity('remote-order'));
    }

    private function query(): QueryBuilder
    {
        return $this->connection->createQueryBuilder()
            ->select('id', 'category', 'name')
            ->from('items');
    }
}

enum DbalBackedContext: string
{
    case Books = 'books';
}

enum DbalUnitContext
{
    case Catalog;
}
