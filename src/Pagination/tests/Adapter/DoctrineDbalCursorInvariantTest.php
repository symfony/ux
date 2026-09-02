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
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Table;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Pagination\Adapter\DoctrineDbalAdapter;
use Symfony\UX\Pagination\Cursor\CursorOrder;

/**
 * Cursor stability invariants for the Doctrine DBAL adapter.
 */
#[CoversClass(DoctrineDbalAdapter::class)]
final class DoctrineDbalCursorInvariantTest extends TestCase
{
    private Connection $connection;
    private DoctrineDbalAdapter $adapter;
    private CursorOrder $ascendingIdOrder;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);
        $this->adapter = new DoctrineDbalAdapter();
        $this->ascendingIdOrder = CursorOrder::byFields(['id'], 'ASC');

        $table = new Table('items');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $this->connection->createSchemaManager()->createTable($table);

        for ($id = 1; $id <= 15; ++$id) {
            $this->connection->insert('items', ['name' => 'Item '.$id]);
        }
    }

    protected function tearDown(): void
    {
        $this->connection->close();
    }

    public function testForwardWalkPartitionsDataset()
    {
        $seen = [];
        $cursor = null;
        do {
            $page = $this->adapter->sliceWithCursor($this->query(), $cursor, 4, $this->ascendingIdOrder);
            $seen = [...$seen, ...array_column($page->items, 'id')];
            $cursor = $page->next;
        } while (null !== $cursor);

        self::assertSame(range(1, 15), $seen);
    }

    public function testBackwardWalkIsExactInverseOfForwardWalk()
    {
        $forwardPages = [];
        $cursor = null;
        $guard = 0;
        do {
            $page = $this->adapter->sliceWithCursor($this->query(), $cursor, 4, $this->ascendingIdOrder);
            $forwardPages[] = array_column($page->items, 'id');
            $cursor = $page->next;
            $lastPage = $page;
        } while (null !== $cursor && ++$guard < 50);

        self::assertCount(4, $forwardPages);

        $backwardPages = [];
        $cursor = $lastPage->previous;
        $guard = 0;
        while (null !== $cursor && ++$guard < 50) {
            $page = $this->adapter->sliceWithCursor($this->query(), $cursor, 4, $this->ascendingIdOrder);
            $backwardPages[] = array_column($page->items, 'id');
            $cursor = $page->previous;
        }

        self::assertSame(\array_slice($forwardPages, 0, -1), array_reverse($backwardPages));
    }

    public function testDeletionBetweenPagesDoesNotSkipSurvivingRows()
    {
        $first = $this->adapter->sliceWithCursor($this->query(), null, 5, $this->ascendingIdOrder);
        self::assertSame(range(1, 5), array_column($first->items, 'id'));

        $this->connection->delete('items', ['id' => 3]);
        $this->connection->delete('items', ['id' => 7]);

        $second = $this->adapter->sliceWithCursor($this->query(), $first->next, 5, $this->ascendingIdOrder);

        self::assertSame([6, 8, 9, 10, 11], array_column($second->items, 'id'));
    }

    public function testInsertionBetweenPagesDoesNotDuplicateRows()
    {
        $first = $this->adapter->sliceWithCursor($this->query(), null, 5, $this->ascendingIdOrder);
        $this->connection->insert('items', ['name' => 'Inserted']);

        $seen = array_column($first->items, 'id');
        $cursor = $first->next;
        while (null !== $cursor) {
            $page = $this->adapter->sliceWithCursor($this->query(), $cursor, 5, $this->ascendingIdOrder);
            $seen = [...$seen, ...array_column($page->items, 'id')];
            $cursor = $page->next;
        }

        self::assertSame($seen, array_unique($seen));
        self::assertSame(range(1, 16), $seen);
    }

    public function testNonTotalOrderIsRejected()
    {
        $this->connection->executeStatement('UPDATE items SET name = ?', ['duplicate']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cursor order is not total');

        $this->adapter->sliceWithCursor($this->query(), null, 5, CursorOrder::byFields(['name'], 'ASC'));
    }

    private function query(): QueryBuilder
    {
        return $this->connection->createQueryBuilder()
            ->select('id', 'name')
            ->from('items');
    }
}
