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
use Symfony\UX\Pagination\Exception\NavigationTooLargeException;
use Symfony\UX\Pagination\Navigation\Navigation;
use Symfony\UX\Pagination\Navigation\NavigationMode;
use Symfony\UX\Pagination\Navigation\PageLink;
use Symfony\UX\Pagination\Navigation\PaginationUrlGenerator;

#[CoversClass(Navigation::class)]
final class NavigationTest extends TestCase
{
    public function testSlidingDefault(): void
    {
        $nav = $this->navigation(5, 20);
        $links = $this->toArray($nav);

        // Should contain: 1, ..., 3, 4, [5], 6, 7, ..., 20
        $pages = array_map(static fn (PageLink $l) => $l->isGap ? '...' : (string) $l->page, $links);

        self::assertContains('1', $pages);
        self::assertContains('5', $pages);
        self::assertContains('20', $pages);
        self::assertContains('...', $pages);
    }

    public function testSlidingFirstPage(): void
    {
        $nav = $this->navigation(1, 20);
        $links = $this->toArray($nav);

        // First page: no gap before, range starts at 1
        $first = $links[0];
        self::assertSame(1, $first->page);
        self::assertTrue($first->isCurrent);
        self::assertFalse($first->isGap);
    }

    public function testSlidingLastPage(): void
    {
        $nav = $this->navigation(20, 20);
        $links = $this->toArray($nav);

        $last = end($links);
        self::assertInstanceOf(PageLink::class, $last);
        self::assertSame(20, $last->page);
        self::assertTrue($last->isCurrent);
    }

    public function testSlidingSize(): void
    {
        $nav = $this->navigation(10, 20, NavigationMode::Sliding, 7);
        $links = $this->toArray($nav);

        self::assertSame(
            ['1', '...', '7', '8', '9', '10', '11', '12', '13', '...', '20'],
            array_map(static fn (PageLink $link): string => $link->isGap ? '...' : (string) $link->page, $links),
        );
    }

    public function testSlidingRejectsEmptySize(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->navigation(10, 20, NavigationMode::Sliding, 0);
    }

    public function testFixedMode(): void
    {
        $nav = $this->navigation(3, 20, NavigationMode::Fixed, 5);
        $links = $this->toArray($nav);

        self::assertNotEmpty($links);

        // All links should have URLs or be gaps
        foreach ($links as $link) {
            if (!$link->isGap) {
                self::assertNotEmpty($link->url);
            }
        }
    }

    public function testFixedRejectsSizeBelowOne(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->navigation(3, 20, NavigationMode::Fixed, 0);
    }

    public function testFullMode(): void
    {
        $nav = $this->navigation(3, 10, NavigationMode::Full, 500);
        $links = $this->toArray($nav);

        // Should have exactly 10 links, no gaps
        self::assertCount(10, $links);

        foreach ($links as $i => $link) {
            self::assertSame($i + 1, $link->page);
            self::assertFalse($link->isGap);
        }

        // Page 3 should be current
        self::assertTrue($links[2]->isCurrent);
        self::assertFalse($links[0]->isCurrent);
    }

    public function testFullModeRefusesAnUnboundedNumberOfLinks(): void
    {
        $nav = $this->navigation(3, 501, NavigationMode::Full, 500);

        $this->expectException(NavigationTooLargeException::class);
        $this->expectExceptionMessage('limited to 500 pages');
        iterator_to_array($nav);
    }

    public function testFullModeAcceptsAnExplicitHigherLimit(): void
    {
        $nav = $this->navigation(3, 501, NavigationMode::Full, 501);

        self::assertCount(501, $nav);
    }

    public function testNullTotalPagesYieldsNothing(): void
    {
        $paginationUrlGenerator = new PaginationUrlGenerator(basePath: '/items');
        $nav = new Navigation(1, null, $paginationUrlGenerator);

        self::assertCount(0, $nav);
    }

    public function testZeroTotalPagesYieldsNothing(): void
    {
        $paginationUrlGenerator = new PaginationUrlGenerator(basePath: '/items');
        $nav = new Navigation(1, 0, $paginationUrlGenerator);

        self::assertCount(0, $nav);
        self::assertSame([], $this->toArray($nav));
    }

    public function testCountSlidingMode(): void
    {
        $nav = $this->navigation(5, 20);

        // Count should match the number of iterated links
        $links = $this->toArray($nav);
        self::assertSame(\count($links), $nav->count());
    }

    public function testCountSlidingModeFirstPage(): void
    {
        $nav = $this->navigation(1, 20);
        $links = $this->toArray($nav);
        self::assertSame(\count($links), $nav->count());
    }

    public function testCountSlidingModeLastPage(): void
    {
        $nav = $this->navigation(20, 20);
        $links = $this->toArray($nav);
        self::assertSame(\count($links), $nav->count());
    }

    public function testCountSlidingModeSmallRange(): void
    {
        // totalPages fits within the range, no gaps
        $nav = $this->navigation(2, 3);
        $links = $this->toArray($nav);
        self::assertSame(\count($links), $nav->count());
    }

    public function testCountFixedMode(): void
    {
        $nav = $this->navigation(7, 20, NavigationMode::Fixed, 5);
        $links = $this->toArray($nav);
        self::assertSame(\count($links), $nav->count());
    }

    public function testCountFixedModeFirstBlock(): void
    {
        $nav = $this->navigation(1, 20, NavigationMode::Fixed, 5);
        $links = $this->toArray($nav);
        self::assertSame(\count($links), $nav->count());
    }

    public function testCountFixedModeLastBlock(): void
    {
        $nav = $this->navigation(20, 20, NavigationMode::Fixed, 5);
        $links = $this->toArray($nav);
        self::assertSame(\count($links), $nav->count());
    }

    public function testCountFullMode(): void
    {
        $nav = $this->navigation(5, 15, NavigationMode::Full, 500);
        self::assertSame(15, $nav->count());
    }

    public function testCountNullTotalPages(): void
    {
        $paginationUrlGenerator = new PaginationUrlGenerator(basePath: '/items');
        $nav = new Navigation(1, null, $paginationUrlGenerator);
        self::assertSame(0, $nav->count());
    }

    public function testCountZeroTotalPages(): void
    {
        $paginationUrlGenerator = new PaginationUrlGenerator(basePath: '/items');
        $nav = new Navigation(1, 0, $paginationUrlGenerator);
        self::assertSame(0, $nav->count());
    }

    public function testFixedModeMiddleBlock(): void
    {
        // Page 12 of 30 with block size 5: current block is [11-15]
        $nav = $this->navigation(12, 30, NavigationMode::Fixed, 5);
        $links = $this->toArray($nav);

        self::assertNotEmpty($links);

        // First block [1-5] + gap + current block [11-15] + gap + last block [26-30]
        $pages = array_map(static fn (PageLink $l) => $l->isGap ? '...' : (string) $l->page, $links);
        self::assertContains('1', $pages);
        self::assertContains('12', $pages);
        self::assertContains('30', $pages);
        self::assertContains('...', $pages);
    }

    public function testFixedModeAdjacentBlocks(): void
    {
        // Page 6 of 20 with block size 5: current block is [6-10], adjacent to first block [1-5]
        $nav = $this->navigation(6, 20, NavigationMode::Fixed, 5);
        $links = $this->toArray($nav);

        self::assertNotEmpty($links);
        $pages = array_map(static fn (PageLink $l) => $l->isGap ? '...' : (string) $l->page, $links);
        self::assertContains('1', $pages);
        self::assertContains('6', $pages);
    }

    public function testFixedModeLastBlock(): void
    {
        // Page 19 of 20 with block size 5: current block is [16-20] which IS the last block
        $nav = $this->navigation(19, 20, NavigationMode::Fixed, 5);
        $links = $this->toArray($nav);

        self::assertNotEmpty($links);
        $last = end($links);
        self::assertInstanceOf(PageLink::class, $last);
        self::assertSame(20, $last->page);
    }

    public function testSlidingNearStartNoGapBefore(): void
    {
        // Page 2 of 20 with default proximity: range starts at 1, no gap before
        $nav = $this->navigation(2, 20);
        $links = $this->toArray($nav);

        $first = $links[0];
        self::assertSame(1, $first->page);
        self::assertFalse($first->isGap);
    }

    public function testSlidingNearEndNoGapAfter(): void
    {
        // Page 19 of 20: range ends at 20, no gap after
        $nav = $this->navigation(19, 20);
        $links = $this->toArray($nav);

        $last = end($links);
        self::assertInstanceOf(PageLink::class, $last);
        self::assertSame(20, $last->page);
        self::assertFalse($last->isGap);
    }

    public function testSlidingGapOnBothSides(): void
    {
        $nav = $this->navigation(10, 20);
        $links = $this->toArray($nav);

        $gaps = array_filter($links, static fn (PageLink $l) => $l->isGap);
        // Should have gaps both before and after the range
        self::assertCount(2, $gaps);
    }

    public function testSlidingNoGapWhenRangeAdjacentToFirst(): void
    {
        // Page 3 of 20 with size 5: range [1..5], no gap before
        $nav = $this->navigation(3, 20, NavigationMode::Sliding, 5);
        $links = $this->toArray($nav);

        // First link should be page 1, not a gap
        self::assertFalse($links[0]->isGap);
        self::assertSame(1, $links[0]->page);
    }

    public function testSlidingNoGapWhenRangeAdjacentToLast(): void
    {
        // Page 18 of 20 with size 5: range [16..20], no gap after
        $nav = $this->navigation(18, 20, NavigationMode::Sliding, 5);
        $links = $this->toArray($nav);

        $last = end($links);
        self::assertInstanceOf(PageLink::class, $last);
        self::assertFalse($last->isGap);
        self::assertSame(20, $last->page);
    }

    private function navigation(
        int $current,
        int $total,
        NavigationMode $mode = NavigationMode::Sliding,
        int $size = 5,
    ): Navigation {
        $paginationUrlGenerator = new PaginationUrlGenerator(basePath: '/items');

        return new Navigation($current, $total, $paginationUrlGenerator, $mode, $size);
    }

    /**
     * @return list<PageLink>
     */
    private function toArray(Navigation $nav): array
    {
        return array_values(iterator_to_array($nav));
    }
}
