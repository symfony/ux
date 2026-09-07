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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Pagination\Adapter\ArrayPaginationAdapter;
use Symfony\UX\Pagination\Cursor\CursorOrder;

#[CoversClass(ArrayPaginationAdapter::class)]
final class ArrayPaginationAdapterTest extends TestCase
{
    private ArrayPaginationAdapter $adapter;
    private CursorOrder $defaultOrder;

    protected function setUp(): void
    {
        $this->adapter = new ArrayPaginationAdapter();
        $this->defaultOrder = CursorOrder::byFields(['id'], 'ASC');
    }

    public function testSupportsArrays(): void
    {
        self::assertTrue($this->adapter->supports([1, 2, 3]));
        self::assertTrue($this->adapter->supports([]));
        self::assertFalse($this->adapter->supports('string'));
        self::assertFalse($this->adapter->supports(42));
        self::assertFalse($this->adapter->supports(new \stdClass()));
    }

    public function testCount(): void
    {
        self::assertSame(0, $this->adapter->count([]));
        self::assertSame(3, $this->adapter->count([1, 2, 3]));
        self::assertSame(100, $this->adapter->count(range(1, 100)));
    }

    public function testCursorContextUsesTheExplicitApplicationContext(): void
    {
        self::assertSame('tenant-a:products', $this->adapter->getCursorContext([], 'tenant-a:products'));
    }

    public function testCursorContextRequiresAnArraySource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->adapter->getCursorContext(new \stdClass(), 'products');
    }

    public function testCursorContextRequiresAnExplicitContext(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('requires an explicit context()');
        $this->adapter->getCursorContext([], null);
    }

    public function testCursorFieldsRequireAnArraySource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->adapter->resolveCursorFields(new \stdClass(), 'id');
    }

    public function testCursorFieldsRejectAnEmptyOrder(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one non-empty cursor field');
        $this->adapter->resolveCursorFields([], []);
    }

    public function testCursorFieldsAreNormalizedToAList(): void
    {
        self::assertSame(['id'], $this->adapter->resolveCursorFields([], 'id'));
    }

    public function testCursorOrderMustBeExplicit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('requires an explicit orderBy()');

        $this->adapter->resolveCursorOrder([], null, null);
    }

    public function testCursorOrderIsResolvedFromFieldsAndDirection(): void
    {
        $order = $this->adapter->resolveCursorOrder([], ['id'], 'desc');

        self::assertSame(['id'], $order->getFields());
        self::assertSame('DESC', $order->getDirection());
    }

    public function testSlice(): void
    {
        $items = range(1, 50);

        self::assertSame([1, 2, 3, 4, 5], $this->adapter->slice($items, 0, 5));
        self::assertSame([11, 12, 13, 14, 15], $this->adapter->slice($items, 10, 5));
        self::assertSame([46, 47, 48, 49, 50], $this->adapter->slice($items, 45, 5));
    }

    public function testSliceBeyondEnd(): void
    {
        $items = [1, 2, 3];

        self::assertSame([3], $this->adapter->slice($items, 2, 5));
        self::assertSame([], $this->adapter->slice($items, 10, 5));
    }

    public function testSliceWithLookahead(): void
    {
        $items = range(1, 50);

        // Page 1 of 10 items: should have more
        [$slice, $hasMore] = $this->adapter->sliceWithLookahead($items, 0, 10);
        self::assertCount(10, $slice);
        self::assertTrue($hasMore);

        // Last page: should not have more
        [$slice, $hasMore] = $this->adapter->sliceWithLookahead($items, 40, 10);
        self::assertCount(10, $slice);
        self::assertFalse($hasMore);

        // Partial last page
        [$slice, $hasMore] = $this->adapter->sliceWithLookahead($items, 45, 10);
        self::assertCount(5, $slice);
        self::assertFalse($hasMore);
    }

    public function testCountThrowsForNonArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->adapter->count('not an array');
    }

    public function testSliceThrowsForNonArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->adapter->slice('not an array', 0, 10);
    }

    public function testSliceWithLookaheadThrowsForNonArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->adapter->sliceWithLookahead('not an array', 0, 10);
    }

    public function testSliceWithCursorFirstPage(): void
    {
        $source = [];
        for ($i = 1; $i <= 30; ++$i) {
            $source[] = ['id' => $i, 'name' => 'Item '.$i];
        }

        $result = $this->adapter->sliceWithCursor($source, null, 10, $this->defaultOrder);

        self::assertCount(10, $result->items);
        self::assertSame(1, $result->items[0]['id']);
        self::assertTrue($result->hasNext);
        self::assertNotNull($result->next);
        self::assertNull($result->previous);
    }

    public function testSliceWithCursorRejectsInvalidDirection(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('direction must be "ASC" or "DESC"');
        CursorOrder::byFields(['id'], 'sideways');
    }

    public function testSliceWithCursorSecondPage(): void
    {
        $source = [];
        for ($i = 1; $i <= 30; ++$i) {
            $source[] = ['id' => $i, 'name' => 'Item '.$i];
        }

        // Get first page cursor
        $page1 = $this->adapter->sliceWithCursor($source, null, 10, $this->defaultOrder);
        $cursor = $page1->next;
        self::assertNotNull($cursor);

        // Second page
        $page2 = $this->adapter->sliceWithCursor($source, $cursor, 10, $this->defaultOrder);

        self::assertCount(10, $page2->items);
        self::assertSame(11, $page2->items[0]['id']);
        self::assertTrue($page2->hasNext);
        self::assertNotNull($page2->previous);
        self::assertNotSame($cursor, $page2->previous);
    }

    public function testSliceWithCursorBackwardReturnsPreviousPage(): void
    {
        $source = [];
        for ($i = 1; $i <= 30; ++$i) {
            $source[] = ['id' => $i, 'name' => 'Item '.$i];
        }

        $page1 = $this->adapter->sliceWithCursor($source, null, 10, $this->defaultOrder);
        $page2 = $this->adapter->sliceWithCursor($source, $page1->next, 10, $this->defaultOrder);
        self::assertSame(11, $page2->items[0]['id']);

        // Navigate backward: must land on page 1 items, in display order
        $back = $this->adapter->sliceWithCursor($source, $page2->previous, 10, $this->defaultOrder);

        self::assertCount(10, $back->items);
        self::assertSame(1, $back->items[0]['id']);
        self::assertSame(10, $back->items[9]['id']);
        // First page again: nothing before it
        self::assertNull($back->previous);
        // The page we came from is the next page
        self::assertNotNull($back->next);
        $forwardAgain = $this->adapter->sliceWithCursor($source, $back->next, 10, $this->defaultOrder);
        self::assertSame(11, $forwardAgain->items[0]['id']);
    }

    public function testSliceWithCursorBackwardFromMiddleKeepsPreviousCursor(): void
    {
        $source = [];
        for ($i = 1; $i <= 30; ++$i) {
            $source[] = ['id' => $i, 'name' => 'Item '.$i];
        }

        $page1 = $this->adapter->sliceWithCursor($source, null, 10, $this->defaultOrder);
        $page2 = $this->adapter->sliceWithCursor($source, $page1->next, 10, $this->defaultOrder);
        $page3 = $this->adapter->sliceWithCursor($source, $page2->next, 10, $this->defaultOrder);
        self::assertSame(21, $page3->items[0]['id']);

        // Back to page 2: a previous page (page 1) still exists
        $back = $this->adapter->sliceWithCursor($source, $page3->previous, 10, $this->defaultOrder);

        self::assertSame(11, $back->items[0]['id']);
        self::assertSame(20, $back->items[9]['id']);
        self::assertNotNull($back->previous);
    }

    public function testSliceWithCursorDateTimeField(): void
    {
        $source = [
            ['id' => 1, 'createdAt' => new \DateTimeImmutable('2024-01-01 10:00:00')],
            ['id' => 2, 'createdAt' => new \DateTimeImmutable('2024-02-01 10:00:00')],
            ['id' => 3, 'createdAt' => new \DateTimeImmutable('2024-03-01 10:00:00')],
        ];

        $order = CursorOrder::byFields(['createdAt'], 'ASC');
        $page1 = $this->adapter->sliceWithCursor($source, null, 2, $order);

        self::assertCount(2, $page1->items);
        self::assertSame(1, $page1->items[0]['id']);
        self::assertNotNull($page1->next);

        $page2 = $this->adapter->sliceWithCursor($source, $page1->next, 2, $order);

        self::assertCount(1, $page2->items);
        self::assertSame(3, $page2->items[0]['id']);
    }

    public function testCursorDateTimesAreOrderedByInstantAcrossTimezones(): void
    {
        $source = [
            // 22:30 UTC: lexicographically later before UTC normalization.
            ['id' => 1, 'createdAt' => new \DateTimeImmutable('2024-01-01 00:30:00+02:00')],
            // 23:00 UTC.
            ['id' => 2, 'createdAt' => new \DateTimeImmutable('2023-12-31 23:00:00+00:00')],
        ];

        $order = CursorOrder::byFields(['createdAt'], 'ASC');
        $first = $this->adapter->sliceWithCursor($source, null, 1, $order);
        self::assertSame([1], array_column($first->items, 'id'));
        self::assertNotNull($first->next);

        $second = $this->adapter->sliceWithCursor($source, $first->next, 1, $order);
        self::assertSame([2], array_column($second->items, 'id'));
    }

    public function testSliceWithCursorLastPage(): void
    {
        $source = [];
        for ($i = 1; $i <= 15; ++$i) {
            $source[] = ['id' => $i, 'name' => 'Item '.$i];
        }

        $page1 = $this->adapter->sliceWithCursor($source, null, 10, $this->defaultOrder);
        $page2 = $this->adapter->sliceWithCursor($source, $page1->next, 10, $this->defaultOrder);

        self::assertCount(5, $page2->items);
        self::assertFalse($page2->hasNext);
        self::assertNull($page2->next);
    }

    public function testSliceWithCursorDescDirection(): void
    {
        $source = [];
        for ($i = 1; $i <= 20; ++$i) {
            $source[] = ['id' => $i, 'name' => 'Item '.$i];
        }

        $result = $this->adapter->sliceWithCursor($source, null, 5, CursorOrder::byFields(['id'], 'DESC'));

        self::assertCount(5, $result->items);
        // DESC order: highest IDs first
        self::assertSame(20, $result->items[0]['id']);
        self::assertTrue($result->hasNext);
    }

    public function testSliceWithCursorMultipleFields(): void
    {
        $source = [
            ['id' => 1, 'price' => 10.0],
            ['id' => 2, 'price' => 10.0],
            ['id' => 3, 'price' => 20.0],
            ['id' => 4, 'price' => 20.0],
            ['id' => 5, 'price' => 30.0],
        ];

        $result = $this->adapter->sliceWithCursor($source, null, 3, CursorOrder::byFields(['price', 'id'], 'ASC'));

        self::assertCount(3, $result->items);
        self::assertTrue($result->hasNext);
    }

    public function testSliceWithCursorMismatchedFieldsThrows(): void
    {
        $source = [['id' => 1, 'price' => 10.0]];

        $cursor = new \Symfony\UX\Pagination\Cursor\CursorBoundary([1]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cursor values count does not match');

        $this->adapter->sliceWithCursor($source, $cursor, 10, CursorOrder::byFields(['price', 'id'], 'ASC'));
    }

    public function testSliceWithCursorThrowsForNonArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->adapter->sliceWithCursor('not an array', null, 10, $this->defaultOrder);
    }

    public function testSliceWithCursorRejectsAnOpaqueOrder(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('requires a field-based cursor order');

        $this->adapter->sliceWithCursor([], null, 10, CursorOrder::byIdentity('remote-order'));
    }

    public function testCursorRejectsDuplicateTuplesWithoutUniqueTieBreaker(): void
    {
        $source = [
            ['id' => 1, 'category' => 'same'],
            ['id' => 2, 'category' => 'same'],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('duplicate cursor tuples');
        $this->adapter->sliceWithCursor($source, null, 10, CursorOrder::byFields(['category'], 'ASC'));
    }

    public function testSliceWithCursorEmptySource(): void
    {
        $result = $this->adapter->sliceWithCursor([], null, 10, $this->defaultOrder);

        self::assertCount(0, $result->items);
        self::assertFalse($result->hasNext);
        self::assertNull($result->next);
    }
}
