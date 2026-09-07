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
use Symfony\UX\Pagination\Adapter\OffsetAdapterInterface;
use Symfony\UX\Pagination\Adapter\PaginationAdapterInterface;
use Symfony\UX\Pagination\Exception\OutOfRangePageException;
use Symfony\UX\Pagination\Navigation\Navigation;
use Symfony\UX\Pagination\Navigation\PageLink;
use Symfony\UX\Pagination\Navigation\PaginationUrlGenerator;
use Symfony\UX\Pagination\Pagination;

#[CoversClass(Pagination::class)]
final class PaginationTest extends TestCase
{
    public function testConstructorRejectsInvalidArguments(): void
    {
        $arguments = [
            [0, 10, 100_000, 'currentPage'],
            [1, 0, 100_000, 'perPage'],
            [1, \PHP_INT_MAX, \PHP_INT_MAX, 'perPage'],
            [1, 10, -1, 'maxOffset'],
        ];

        foreach ($arguments as [$page, $perPage, $maxOffset, $message]) {
            try {
                new Pagination(
                    source: [],
                    adapter: new ArrayPaginationAdapter(),
                    currentPage: $page,
                    perPage: $perPage,
                    paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
                    maxOffset: $maxOffset,
                );
                self::fail('Invalid constructor arguments must be rejected.');
            } catch (\InvalidArgumentException $exception) {
                self::assertStringContainsString($message, $exception->getMessage());
            }
        }
    }

    public function testConstructorRejectsOffsetIntegerOverflow(): void
    {
        $this->expectException(\Symfony\UX\Pagination\Exception\OffsetLimitExceededException::class);
        new Pagination(
            source: [],
            adapter: new ArrayPaginationAdapter(),
            currentPage: \PHP_INT_MAX,
            perPage: 2,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
            maxOffset: \PHP_INT_MAX,
        );
    }

    public function testConstructorRejectsOffsetAboveConfiguredLimit(): void
    {
        $this->expectException(\Symfony\UX\Pagination\Exception\OffsetLimitExceededException::class);
        new Pagination(
            source: [],
            adapter: new ArrayPaginationAdapter(),
            currentPage: 3,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
            maxOffset: 10,
        );
    }

    public function testConstructorRequiresTheCapabilityMatchingTheSelectedMode(): void
    {
        $adapter = new class implements PaginationAdapterInterface {
            public function supports(mixed $source): bool
            {
                return true;
            }
        };

        foreach ([[false, 'Offset'], [true, 'Lookahead']] as [$lookahead, $mode]) {
            try {
                new Pagination(
                    source: [],
                    adapter: $adapter,
                    currentPage: 1,
                    perPage: 10,
                    paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
                    lookahead: $lookahead,
                );
                self::fail($mode.' pagination must require its adapter capability.');
            } catch (\InvalidArgumentException $exception) {
                self::assertStringContainsString($mode.' pagination requires', $exception->getMessage());
            }
        }
    }

    public function testIterateReturnsItems(): void
    {
        $pagination = $this->paginate(range(1, 100), 1, 10);

        $items = [];
        foreach ($pagination as $item) {
            $items[] = $item;
        }

        self::assertSame([1, 2, 3, 4, 5, 6, 7, 8, 9, 10], $items);
    }

    public function testItemsReturnsArray(): void
    {
        $pagination = $this->paginate(range(1, 50), 2, 10);

        self::assertSame([11, 12, 13, 14, 15, 16, 17, 18, 19, 20], $pagination->getItems());
    }

    public function testCountReturnsItemsOnThisPage(): void
    {
        $pagination = $this->paginate(range(1, 25), 3, 10);

        self::assertSame(5, $pagination->count());
        self::assertCount(5, $pagination);
    }

    public function testMapTransformsItemsToAnotherType(): void
    {
        $pagination = $this->paginate([1, 2, 3], 1, 10);
        $labels = $pagination->map(static fn (int $item): string => 'item-'.$item);

        self::assertSame(['item-1', 'item-2', 'item-3'], $labels->getItems());
        // Original unchanged
        self::assertSame([1, 2, 3], $pagination->getItems());
    }

    public function testMetadata(): void
    {
        $pagination = $this->paginate(range(1, 100), 3, 10);

        self::assertSame(3, $pagination->getCurrentPage());
        self::assertSame(10, $pagination->getItemsPerPage());
        self::assertSame(100, $pagination->getTotalItems());
        self::assertSame(10, $pagination->getTotalPages());
        self::assertSame(21, $pagination->getFirstItemNumber());
        self::assertSame(30, $pagination->getLastItemNumber());
        self::assertFalse($pagination->isEmpty());
    }

    public function testEmptyResult(): void
    {
        $pagination = $this->paginate([], 1, 10);

        self::assertTrue($pagination->isEmpty());
        self::assertSame(0, $pagination->count());
        self::assertSame(0, $pagination->getTotalItems());
        self::assertSame(1, $pagination->getTotalPages());
        self::assertNull($pagination->getFirstItemNumber());
        self::assertNull($pagination->getLastItemNumber());
    }

    public function testItemNumbersUseTheActualLastPageSize(): void
    {
        $pagination = $this->paginate(range(1, 25), 3, 10);

        self::assertSame(21, $pagination->getFirstItemNumber());
        self::assertSame(25, $pagination->getLastItemNumber());
    }

    public function testNavigationStateFirstPage(): void
    {
        $pagination = $this->paginate(range(1, 100), 1, 10);

        self::assertTrue($pagination->hasNext());
        self::assertFalse($pagination->hasPrevious());
        self::assertTrue($pagination->isFirst());
        self::assertFalse($pagination->isLast());
    }

    public function testNavigationStateMiddlePage(): void
    {
        $pagination = $this->paginate(range(1, 100), 5, 10);

        self::assertTrue($pagination->hasNext());
        self::assertTrue($pagination->hasPrevious());
        self::assertFalse($pagination->isFirst());
        self::assertFalse($pagination->isLast());
    }

    public function testNavigationStateLastPage(): void
    {
        $pagination = $this->paginate(range(1, 100), 10, 10);

        self::assertFalse($pagination->hasNext());
        self::assertTrue($pagination->hasPrevious());
        self::assertFalse($pagination->isFirst());
        self::assertTrue($pagination->isLast());
    }

    public function testSinglePage(): void
    {
        $pagination = $this->paginate([1, 2, 3], 1, 10);

        self::assertFalse($pagination->hasNext());
        self::assertFalse($pagination->hasPrevious());
        self::assertTrue($pagination->isFirst());
        self::assertTrue($pagination->isLast());
    }

    public function testUrls(): void
    {
        $pagination = $this->paginate(range(1, 100), 5, 10);

        self::assertStringContainsString('page=5', $pagination->getUrl(5));
        self::assertStringContainsString('page=6', $pagination->getNextUrl() ?? '');
        self::assertStringContainsString('page=4', $pagination->getPreviousUrl() ?? '');
        self::assertNotNull($pagination->getFirstUrl());

        $this->expectException(\Symfony\UX\Pagination\Exception\RuntimeException::class);
        $pagination->getAbsoluteUrl(5);
    }

    public function testUrlsOnFirstPage(): void
    {
        $pagination = $this->paginate(range(1, 100), 1, 10);

        self::assertNull($pagination->getPreviousUrl());
        self::assertNotNull($pagination->getNextUrl());
    }

    public function testUrlsOnLastPage(): void
    {
        $pagination = $this->paginate(range(1, 100), 10, 10);

        self::assertNull($pagination->getNextUrl());
        self::assertNotNull($pagination->getPreviousUrl());
    }

    public function testPagesReturnNavigation(): void
    {
        $pagination = $this->paginate(range(1, 100), 5, 10);
        $pages = $pagination->getPages();

        self::assertInstanceOf(Navigation::class, $pages);

        $links = iterator_to_array($pages);
        self::assertNotEmpty($links);

        foreach ($links as $link) {
            self::assertInstanceOf(PageLink::class, $link);
        }
    }

    public function testSlidingNavigation(): void
    {
        $pagination = $this->paginate(range(1, 200), 10, 10);
        $links = iterator_to_array($pagination->getPages());

        // Should have first page, gap, range around current, gap, last page
        $pageNumbers = array_map(static fn (PageLink $l) => $l->page, $links);
        self::assertContains(1, $pageNumbers);
        self::assertContains(10, $pageNumbers);
        self::assertContains(20, $pageNumbers);

        // Should have gaps
        $gaps = array_filter($links, static fn (PageLink $l) => $l->isGap);
        self::assertNotEmpty($gaps);

        // Current page should be marked
        $current = array_filter($links, static fn (PageLink $l) => $l->isCurrent);
        self::assertCount(1, $current);
        $currentLink = reset($current);
        self::assertInstanceOf(PageLink::class, $currentLink);
        self::assertSame(10, $currentLink->page);
    }

    public function testLookaheadMode(): void
    {
        $source = range(1, 50);
        $adapter = new ArrayPaginationAdapter();
        $paginationUrlGenerator = new PaginationUrlGenerator(basePath: '/items');

        $pagination = new Pagination(
            source: $source,
            adapter: $adapter,
            currentPage: 1,
            perPage: 10,
            paginationUrlGenerator: $paginationUrlGenerator,
            lookahead: true,
        );

        // Total should be null in lookahead mode
        self::assertNull($pagination->getTotalItems());
        self::assertNull($pagination->getTotalPages());

        self::assertTrue($pagination->hasNext());

        // Items should be correct count
        self::assertCount(10, $pagination->getItems());
    }

    public function testLookaheadLastPage(): void
    {
        $source = range(1, 25);
        $adapter = new ArrayPaginationAdapter();
        $paginationUrlGenerator = new PaginationUrlGenerator(basePath: '/items');

        $pagination = new Pagination(
            source: $source,
            adapter: $adapter,
            currentPage: 3,
            perPage: 10,
            paginationUrlGenerator: $paginationUrlGenerator,
            lookahead: true,
        );

        self::assertFalse($pagination->hasNext());
        self::assertCount(5, $pagination->getItems());
    }

    public function testItemsAreLazyLoaded(): void
    {
        $sliceCallCount = 0;
        $innerAdapter = new ArrayPaginationAdapter();
        $source = range(1, 100);

        $adapter = new class($innerAdapter, $sliceCallCount) implements OffsetAdapterInterface {
            public function __construct(
                private readonly ArrayPaginationAdapter $inner,
                private int &$callCount,
            ) {
            }

            public function supports(mixed $source): bool
            {
                return $this->inner->supports($source);
            }

            public function slice(mixed $source, int $offset, int $limit): array
            {
                ++$this->callCount;

                return $this->inner->slice($source, $offset, $limit);
            }

            public function count(mixed $source): int
            {
                return $this->inner->count($source);
            }
        };

        $paginationUrlGenerator = new PaginationUrlGenerator(basePath: '/items');

        $pagination = new Pagination(
            source: $source,
            adapter: $adapter,
            currentPage: 1,
            perPage: 10,
            paginationUrlGenerator: $paginationUrlGenerator,
        );

        // Not executed yet
        self::assertSame(0, $sliceCallCount);

        // First access triggers query
        $pagination->getItems();
        self::assertSame(1, $sliceCallCount);

        // Second access reuses cache
        $pagination->getItems();
        self::assertSame(1, $sliceCallCount);

        // foreach also reuses cache
        foreach ($pagination as $item) {
            // iterate
        }
        self::assertSame(1, $sliceCallCount);
    }

    public function testJsonSerialize(): void
    {
        $pagination = $this->paginate(range(1, 30), 2, 10);
        $json = $pagination->jsonSerialize();

        self::assertSame([11, 12, 13, 14, 15, 16, 17, 18, 19, 20], $json['items']);
        self::assertSame(2, $json['current_page']);
        self::assertSame(10, $json['per_page']);
        self::assertTrue($json['has_next']);
        self::assertTrue($json['has_previous']);
        self::assertSame(30, $json['total_items']);
        self::assertSame(3, $json['total_pages']);
    }

    public function testInfo(): void
    {
        $pagination = $this->paginate(range(1, 100), 2, 10);

        self::assertSame('Showing 11-20 of 100', $pagination->getInfo());
    }

    public function testInfoLastPage(): void
    {
        $pagination = $this->paginate(range(1, 25), 3, 10);

        self::assertSame('Showing 21-25 of 25', $pagination->getInfo());
    }

    public function testQueryParamReturnsDefault(): void
    {
        $pagination = $this->paginate(range(1, 10), 1, 10);

        self::assertSame('page', $pagination->getPageParameterName());
    }

    public function testQueryParamReturnsCustomValue(): void
    {
        $paginationUrlGenerator = new PaginationUrlGenerator(queryParam: 'p', basePath: '/items');
        $pagination = new Pagination(
            source: range(1, 10),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 1,
            perPage: 10,
            paginationUrlGenerator: $paginationUrlGenerator,
        );

        self::assertSame('p', $pagination->getPageParameterName());
    }

    public function testIsOutOfRangeTrue(): void
    {
        $pagination = $this->paginate(range(1, 30), 5, 10);

        self::assertTrue($pagination->isOutOfRange());
    }

    public function testIsOutOfRangeFalse(): void
    {
        $pagination = $this->paginate(range(1, 100), 5, 10);

        self::assertFalse($pagination->isOutOfRange());
    }

    public function testIsOutOfRangeFalseInLookaheadMode(): void
    {
        $pagination = new Pagination(
            source: range(1, 10),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 100,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
            lookahead: true,
        );

        self::assertFalse($pagination->isOutOfRange());
    }

    public function testThrowOnOutOfRangeThrows(): void
    {
        $pagination = $this->paginate(range(1, 30), 5, 10);

        $this->expectException(OutOfRangePageException::class);
        $this->expectExceptionMessage('Page 5 is out of range. Last page is 3.');

        $pagination->throwOnOutOfRange();
    }

    public function testThrowOnOutOfRangeReturnsSelfWhenInRange(): void
    {
        $pagination = $this->paginate(range(1, 100), 5, 10);

        $result = $pagination->throwOnOutOfRange();

        self::assertSame($pagination, $result);
    }

    public function testMeta(): void
    {
        $pagination = $this->paginate(range(1, 50), 2, 10);
        $meta = $pagination->getMetadata();

        self::assertSame(2, $meta['current_page']);
        self::assertSame(10, $meta['per_page']);
        self::assertTrue($meta['has_next']);
        self::assertTrue($meta['has_previous']);
        self::assertSame(50, $meta['total_items']);
        self::assertSame(5, $meta['total_pages']);
    }

    public function testMetaInLookaheadMode(): void
    {
        $pagination = new Pagination(
            source: range(1, 50),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 1,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
            lookahead: true,
        );

        $meta = $pagination->getMetadata();

        self::assertArrayNotHasKey('total_items', $meta);
        self::assertArrayNotHasKey('total_pages', $meta);
        self::assertTrue($meta['has_next']);
    }

    public function testLinks(): void
    {
        $pagination = $this->paginate(range(1, 50), 2, 10);
        $links = $pagination->getLinks();

        self::assertArrayHasKey('first', $links);
        self::assertArrayHasKey('last', $links);
        self::assertArrayHasKey('prev', $links);
        self::assertArrayHasKey('next', $links);

        self::assertNotNull($links['first']);
        self::assertNotNull($links['last']);
        self::assertNotNull($links['prev']);
        self::assertNotNull($links['next']);
    }

    public function testLinksFirstPage(): void
    {
        $pagination = $this->paginate(range(1, 50), 1, 10);
        $links = $pagination->getLinks();

        self::assertNull($links['prev']);
        self::assertNotNull($links['next']);
    }

    public function testLinksLastPage(): void
    {
        $pagination = $this->paginate(range(1, 50), 5, 10);
        $links = $pagination->getLinks();

        self::assertNotNull($links['prev']);
        self::assertNull($links['next']);
    }

    public function testLastUrl(): void
    {
        $pagination = $this->paginate(range(1, 50), 1, 10);

        $lastUrl = $pagination->getLastUrl();

        self::assertNotNull($lastUrl);
        self::assertStringContainsString('page=5', $lastUrl);
    }

    public function testLastUrlIsUnknownWithLookahead(): void
    {
        $pagination = new Pagination(
            source: range(1, 20),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 1,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
            lookahead: true,
        );

        self::assertNull($pagination->getLastUrl());
    }

    public function testFirstUrlOmitsPageParam(): void
    {
        $pagination = $this->paginate(range(1, 100), 5, 10);

        $firstUrl = $pagination->getFirstUrl();

        self::assertStringNotContainsString('page=', $firstUrl);
    }

    public function testTotalInt(): void
    {
        $pagination = new Pagination(
            source: range(1, 100),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 1,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
            total: 42,
        );

        self::assertSame(42, $pagination->getTotalItems());
        self::assertSame(5, $pagination->getTotalPages());
    }

    public function testTotalPagesKeepsIntegerPrecisionForLargeCounts(): void
    {
        $pagination = new Pagination(
            source: [],
            adapter: new ArrayPaginationAdapter(),
            currentPage: 1,
            perPage: 2,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
            total: \PHP_INT_MAX,
        );

        self::assertSame(intdiv(\PHP_INT_MAX, 2) + 1, $pagination->getTotalPages());
    }

    public function testNegativeCountIsRejected(): void
    {
        $adapter = $this->createStub(OffsetAdapterInterface::class);
        $adapter->method('count')->willReturn(-1);
        $pagination = new Pagination(
            source: [],
            adapter: $adapter,
            currentPage: 1,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('greater than or equal to 0');

        $pagination->getTotalItems();
    }

    public function testTotalCallable(): void
    {
        $callCount = 0;
        $pagination = new Pagination(
            source: range(1, 100),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 1,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
            total: static function () use (&$callCount) {
                ++$callCount;

                return 55;
            },
        );

        self::assertSame(55, $pagination->getTotalItems());
        self::assertSame(55, $pagination->getTotalItems()); // cached
        self::assertSame(1, $callCount);
    }

    public function testInfoEmptyWithTotal(): void
    {
        // Page beyond the last page: count is 0, total is known
        $pagination = $this->paginate(range(1, 10), 5, 10);

        self::assertSame('No items', $pagination->getInfo());
    }

    public function testInfoEmptyWithoutTotal(): void
    {
        $pagination = new Pagination(
            source: [],
            adapter: new ArrayPaginationAdapter(),
            currentPage: 1,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
            lookahead: true,
        );

        self::assertSame('No items', $pagination->getInfo());
    }

    public function testInfoWithFormatter(): void
    {
        $translator = $this->createStub(\Symfony\Contracts\Translation\TranslatorInterface::class);
        $translator->method('trans')->willReturn('Page 2 sur 5');
        $formatter = new \Symfony\UX\Pagination\PaginationInfoFormatter($translator);

        $pagination = new Pagination(
            source: range(1, 50),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 2,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
            infoFormatter: $formatter,
        );

        self::assertSame('Page 2 sur 5', $pagination->getInfo());
    }

    public function testInfoWithoutTotal(): void
    {
        $pagination = new Pagination(
            source: range(1, 100),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 2,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
            lookahead: true,
        );

        self::assertSame('Showing 11-20', $pagination->getInfo());
    }

    public function testTotalCallableReturningNonIntThrows(): void
    {
        $pagination = new Pagination(
            source: range(1, 100),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 1,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
            total: static fn () => 'not an int', // @phpstan-ignore return.type
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Total callable must return an int');

        $pagination->getTotalItems();
    }

    public function testPerPageOfOnePaginatesItemByItem(): void
    {
        $middle = $this->paginate(range(1, 3), 2, 1);

        self::assertSame([2], $middle->getItems());
        self::assertSame(3, $middle->getTotalPages());
        self::assertTrue($middle->hasPrevious());
        self::assertTrue($middle->hasNext());
        self::assertFalse($middle->isFirst());
        self::assertFalse($middle->isLast());

        $last = $this->paginate(range(1, 3), 3, 1);

        self::assertSame([3], $last->getItems());
        self::assertTrue($last->isLast());
        self::assertFalse($last->hasNext());
    }

    private function paginate(array $source, int $page, int $perPage): Pagination
    {
        $adapter = new ArrayPaginationAdapter();
        $paginationUrlGenerator = new PaginationUrlGenerator(basePath: '/items');

        return new Pagination(
            source: $source,
            adapter: $adapter,
            currentPage: $page,
            perPage: $perPage,
            paginationUrlGenerator: $paginationUrlGenerator,
        );
    }
}
