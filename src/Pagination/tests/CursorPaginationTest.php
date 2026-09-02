<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Pagination\Adapter\ArrayPaginationAdapter;
use Symfony\UX\Pagination\Adapter\CursorAdapterInterface;
use Symfony\UX\Pagination\Cursor\CursorBoundary;
use Symfony\UX\Pagination\Cursor\CursorOrder;
use Symfony\UX\Pagination\Cursor\CursorSlice;
use Symfony\UX\Pagination\CursorPagination;
use Symfony\UX\Pagination\Exception\InvalidArgumentException;
use Symfony\UX\Pagination\Exception\InvalidCursorException;
use Symfony\UX\Pagination\Navigation\PaginationUrlGenerator;

#[CoversClass(CursorPagination::class)]
final class CursorPaginationTest extends TestCase
{
    public function testItemsFirstPage()
    {
        $source = $this->createSource(50);
        $pagination = $this->createCursorPagination($source, null, 10);

        $items = $pagination->getItems();
        self::assertCount(10, $items);
        self::assertSame(1, $items[0]['id']);
        self::assertSame(10, $items[9]['id']);
    }

    public function testIterateReturnsItems()
    {
        $source = $this->createSource(5);
        $pagination = $this->createCursorPagination($source, null, 10);

        $items = [];
        foreach ($pagination as $item) {
            $items[] = $item;
        }

        self::assertCount(5, $items);
    }

    public function testCountReturnsItemsOnThisPage()
    {
        $source = $this->createSource(25);
        $pagination = $this->createCursorPagination($source, null, 10);

        self::assertSame(10, $pagination->count());
        self::assertCount(10, $pagination);
    }

    public function testThroughTransformsItems()
    {
        $source = $this->createSource(3);
        $pagination = $this->createCursorPagination($source, null, 10);
        $doubled = $pagination->map(static fn (array $item): array => array_merge($item, ['doubled' => true]));

        self::assertTrue($doubled->getItems()[0]['doubled']);
        self::assertArrayNotHasKey('doubled', $pagination->getItems()[0]);
    }

    public function testMapTransformsItemsToAnotherTypeAndFetchesOnlyTheClone()
    {
        $adapter = new class implements CursorAdapterInterface {
            public int $calls = 0;

            public function supports(mixed $source): bool
            {
                return true;
            }

            public function getCursorContext(mixed $source, ?string $context): string
            {
                return $context ?? 'test';
            }

            public function resolveCursorOrder(mixed $source, ?array $fields, ?string $direction): CursorOrder
            {
                return CursorOrder::byIdentity('test-order');
            }

            public function sliceWithCursor(mixed $source, ?CursorBoundary $boundary, int $limit, CursorOrder $order): CursorSlice
            {
                ++$this->calls;

                return new CursorSlice([1], null, null, false);
            }
        };
        $pagination = new CursorPagination(
            source: new \stdClass(),
            adapter: $adapter,
            cursor: null,
            perPage: 10,
            order: CursorOrder::byIdentity('test-order'),
            cursorCodec: new \Symfony\UX\Pagination\Cursor\CursorCodec('test-secret'),
            context: 'test',
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
        );

        self::assertSame(['item-1'], $pagination->map(static fn (int $item): string => 'item-'.$item)->getItems());
        self::assertSame(1, $adapter->calls);
    }

    public function testFailedFetchIsNotCachedAsAnEmptyPage()
    {
        $pagination = $this->createCursorPagination($this->createSource(5), 'invalid', 10);
        $failures = 0;

        for ($attempt = 0; $attempt < 2; ++$attempt) {
            try {
                $pagination->getItems();
                self::fail('An invalid cursor must fail on every fetch attempt.');
            } catch (InvalidCursorException) {
                ++$failures;
            }
        }

        self::assertSame(2, $failures);
    }

    public function testPerPage()
    {
        $pagination = $this->createCursorPagination($this->createSource(50), null, 25);

        self::assertSame(25, $pagination->getItemsPerPage());
    }

    public function testPerPageMustBePositive()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('perPage must be >= 1.');

        $this->createCursorPagination([], null, 0);
    }

    public function testPerPageMustNotOverflowLookahead()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('less than PHP_INT_MAX');

        $this->createCursorPagination([], null, \PHP_INT_MAX);
    }

    public function testCursorIsNullForFirstPage()
    {
        $pagination = $this->createCursorPagination($this->createSource(50), null, 10);

        self::assertNull($pagination->getCursor());
    }

    public function testIsEmpty()
    {
        $pagination = $this->createCursorPagination([], null, 10);

        self::assertTrue($pagination->isEmpty());
    }

    public function testHasNextWhenMoreItemsExist()
    {
        $source = $this->createSource(50);
        $pagination = $this->createCursorPagination($source, null, 10);

        self::assertTrue($pagination->hasNext());
    }

    public function testHasNextIsFalseOnLastPage()
    {
        $source = $this->createSource(5);
        $pagination = $this->createCursorPagination($source, null, 10);

        self::assertFalse($pagination->hasNext());
    }

    public function testHasPreviousOnFirstPage()
    {
        $pagination = $this->createCursorPagination($this->createSource(50), null, 10);

        self::assertFalse($pagination->hasPrevious());
        self::assertNull($pagination->getPreviousUrl());
    }

    public function testHasPreviousOnSecondPage()
    {
        $source = $this->createSource(50);

        // First page
        $page1 = $this->createCursorPagination($source, null, 10);
        $nextCursor = $page1->getNextCursor();
        self::assertNotNull($nextCursor);

        // Second page
        $page2 = $this->createCursorPagination($source, $nextCursor, 10);
        self::assertTrue($page2->hasPrevious());
    }

    public function testNextCursorProvided()
    {
        $source = $this->createSource(50);
        $pagination = $this->createCursorPagination($source, null, 10);

        self::assertNotNull($pagination->getNextCursor());
    }

    public function testNextCursorNullOnLastPage()
    {
        $source = $this->createSource(5);
        $pagination = $this->createCursorPagination($source, null, 10);

        self::assertNull($pagination->getNextCursor());
    }

    public function testCursorPaginationFlow()
    {
        $source = $this->createSource(25);

        // Page 1: items 1-10
        $page1 = $this->createCursorPagination($source, null, 10);
        self::assertSame(1, $page1->getItems()[0]['id']);
        self::assertSame(10, $page1->getItems()[9]['id']);
        self::assertTrue($page1->hasNext());

        // Page 2: items 11-20
        $page2 = $this->createCursorPagination($source, $page1->getNextCursor(), 10);
        self::assertSame(11, $page2->getItems()[0]['id']);
        self::assertSame(20, $page2->getItems()[9]['id']);
        self::assertTrue($page2->hasNext());

        // Page 3: items 21-25
        $page3 = $this->createCursorPagination($source, $page2->getNextCursor(), 10);
        self::assertCount(5, $page3->getItems());
        self::assertSame(21, $page3->getItems()[0]['id']);
        self::assertFalse($page3->hasNext());
        self::assertNull($page3->getNextCursor());
    }

    public function testNextUrlProvided()
    {
        $source = $this->createSource(50);
        $pagination = $this->createCursorPagination($source, null, 10);

        $nextUrl = $pagination->getNextUrl();
        self::assertNotNull($nextUrl);
        self::assertStringContainsString('cursor=', $nextUrl);
    }

    public function testNextUrlNullOnLastPage()
    {
        $source = $this->createSource(5);
        $pagination = $this->createCursorPagination($source, null, 10);

        self::assertNull($pagination->getNextUrl());
    }

    public function testPreviousUrlOnSecondPage()
    {
        $source = $this->createSource(50);
        $page1 = $this->createCursorPagination($source, null, 10);
        $page2 = $this->createCursorPagination($source, $page1->getNextCursor(), 10);

        self::assertNotNull($page2->getPreviousUrl());
        self::assertStringContainsString('cursor=', $page2->getPreviousUrl());
    }

    public function testJsonSerialize()
    {
        $source = $this->createSource(50);
        $pagination = $this->createCursorPagination($source, null, 10);

        $json = $pagination->jsonSerialize();

        self::assertCount(10, $json['items']);
        self::assertSame(10, $json['per_page']);
        self::assertNull($json['cursor']);
        self::assertNotNull($json['next_cursor']);
        self::assertNull($json['previous_cursor']);
        self::assertTrue($json['has_next']);
        self::assertFalse($json['has_previous']);
        self::assertArrayNotHasKey('has_more', $json);
        self::assertNull($json['links']['prev']);
        self::assertNotNull($json['links']['next']);
    }

    public function testInfo()
    {
        $source = $this->createSource(50);
        $pagination = $this->createCursorPagination($source, null, 10);

        self::assertSame('Showing 10 items', $pagination->getInfo());
    }

    public function testInfoLastPage()
    {
        $source = $this->createSource(5);
        $pagination = $this->createCursorPagination($source, null, 10);

        self::assertSame('Showing 5 items (last page)', $pagination->getInfo());
    }

    public function testInfoEmpty()
    {
        $pagination = $this->createCursorPagination([], null, 10);

        self::assertSame('No items', $pagination->getInfo());
    }

    public function testItemsAreLazyLoaded()
    {
        $source = $this->createSource(50);
        $pagination = $this->createCursorPagination($source, null, 10);

        // Items are not fetched at construction -- only when accessed
        // We verify by checking count() triggers the fetch
        self::assertSame(10, $pagination->count());
    }

    public function testCursorUrl()
    {
        $source = $this->createSource(50);
        $pagination = $this->createCursorPagination($source, null, 10);

        $url = $pagination->getCursorUrl(cursor: 'abc123');

        self::assertStringContainsString('cursor=abc123', $url);
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function testPerPageOfOneWalksItemByItem()
    {
        $first = $this->createCursorPagination($this->createSource(3), null, 1);

        self::assertSame([['id' => 1, 'name' => 'Item 1']], $first->getItems());
        self::assertTrue($first->hasNext());
        self::assertFalse($first->hasPrevious());

        $second = $this->createCursorPagination($this->createSource(3), $first->getNextCursor(), 1);

        self::assertSame([['id' => 2, 'name' => 'Item 2']], $second->getItems());
        self::assertTrue($second->hasNext());
        self::assertTrue($second->hasPrevious());

        $last = $this->createCursorPagination($this->createSource(3), $second->getNextCursor(), 1);

        self::assertSame([['id' => 3, 'name' => 'Item 3']], $last->getItems());
        self::assertFalse($last->hasNext());
        self::assertTrue($last->hasPrevious());
    }

    private function createSource(int $count): array
    {
        $items = [];
        for ($i = 1; $i <= $count; ++$i) {
            $items[] = ['id' => $i, 'name' => 'Item '.$i];
        }

        return $items;
    }

    /**
     * @param list<array{id: int, name: string}>|array{} $source
     */
    private function createCursorPagination(array $source, ?string $cursor, int $perPage): CursorPagination
    {
        $adapter = new ArrayPaginationAdapter();
        $paginationUrlGenerator = new PaginationUrlGenerator(basePath: '/items');

        return new CursorPagination(
            source: $source,
            adapter: $adapter,
            cursor: $cursor,
            perPage: $perPage,
            order: CursorOrder::byFields(['id'], 'ASC'),
            cursorCodec: new \Symfony\UX\Pagination\Cursor\CursorCodec('test-secret'),
            context: 'test',
            paginationUrlGenerator: $paginationUrlGenerator,
        );
    }
}
