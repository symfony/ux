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

/**
 * Invariant tests for cursor pagination on the array adapter.
 *
 * These do not test examples but properties: walking all pages must
 * partition the dataset (no duplicate, no gap), backward navigation must
 * be the exact inverse of forward navigation, and mutations between two
 * page fetches must not create offset-shift skips or duplicates while the
 * surviving items keep their ordered values.
 */
#[CoversClass(ArrayPaginationAdapter::class)]
final class ArrayCursorInvariantTest extends TestCase
{
    private ArrayPaginationAdapter $adapter;
    private CursorOrder $ascendingIdOrder;

    protected function setUp(): void
    {
        $this->adapter = new ArrayPaginationAdapter();
        $this->ascendingIdOrder = CursorOrder::byFields(['id'], 'ASC');
    }

    public function testForwardWalkPartitionsDatasetAsc(): void
    {
        $source = $this->makeSource(23);

        $ids = $this->collectIds($this->walkForward($source, 5, 'id', 'ASC'));

        self::assertSame(range(1, 23), $ids);
    }

    public function testForwardWalkPartitionsDatasetDesc(): void
    {
        $source = $this->makeSource(23);

        $ids = $this->collectIds($this->walkForward($source, 5, 'id', 'DESC'));

        self::assertSame(range(23, 1), $ids);
    }

    public function testForwardWalkPartitionsDatasetWithCompositeCursor(): void
    {
        // Non-unique first field: 23 items sharing only 3 distinct prices
        $source = [];
        for ($i = 1; $i <= 23; ++$i) {
            $source[] = ['id' => $i, 'price' => (float) ($i % 3)];
        }

        $pages = $this->walkForward($source, 4, ['price', 'id'], 'ASC');
        $ids = $this->collectIds($pages);

        self::assertCount(23, $ids, 'Every item must be seen exactly once');
        self::assertCount(23, array_unique($ids), 'No item may be duplicated');

        // Display order must follow (price, id) lexicographically
        $expected = $source;
        usort($expected, static fn ($a, $b) => [$a['price'], $a['id']] <=> [$b['price'], $b['id']]);
        self::assertSame(array_column($expected, 'id'), $ids);
    }

    public function testBackwardWalkIsExactInverseOfForwardWalk(): void
    {
        $source = $this->makeSource(23);

        $forwardPages = $this->walkForward($source, 5, 'id', 'ASC');
        self::assertCount(5, $forwardPages);

        // Walk backward from the last page using previousCursor only
        $backwardPages = [];
        $cursor = $forwardPages[\count($forwardPages) - 1]->previous;
        $guard = 0;
        while (null !== $cursor && ++$guard < 50) {
            $page = $this->adapter->sliceWithCursor($source, $cursor, 5, $this->ascendingIdOrder);
            $backwardPages[] = $page;
            $cursor = $page->previous;
        }

        // Backward pages, reversed, must equal forward pages minus the last one
        $expected = array_map(
            static fn ($page) => array_column($page->items, 'id'),
            \array_slice($forwardPages, 0, -1),
        );
        $actual = array_reverse(array_map(
            static fn ($page) => array_column($page->items, 'id'),
            $backwardPages,
        ));

        self::assertSame($expected, $actual);
    }

    public function testDeletionBetweenPagesNeverSkipsSurvivors(): void
    {
        $source = $this->makeSource(15);

        $page1 = $this->adapter->sliceWithCursor($source, null, 5, $this->ascendingIdOrder);
        self::assertSame(range(1, 5), array_column($page1->items, 'id'));

        // Delete one already-seen item and one upcoming item
        $mutated = array_values(array_filter($source, static fn ($item) => !\in_array($item['id'], [3, 7], true)));

        $page2 = $this->adapter->sliceWithCursor($mutated, $page1->next, 5, $this->ascendingIdOrder);

        // Offset pagination would skip item 6 here; cursors must not
        self::assertSame([6, 8, 9, 10, 11], array_column($page2->items, 'id'));
    }

    public function testInsertionBetweenPagesNeverDuplicates(): void
    {
        $source = $this->makeSource(15);

        $page1 = $this->adapter->sliceWithCursor($source, null, 5, $this->ascendingIdOrder);

        // Insert one item before the cursor position and one after
        $mutated = [...$source, ['id' => 0, 'name' => 'before'], ['id' => 99, 'name' => 'after']];

        $seen = array_column($page1->items, 'id');
        $cursor = $page1->next;
        $guard = 0;
        while (null !== $cursor && ++$guard < 50) {
            $page = $this->adapter->sliceWithCursor($mutated, $cursor, 5, $this->ascendingIdOrder);
            $seen = [...$seen, ...array_column($page->items, 'id')];
            $cursor = $page->next;
        }

        self::assertSame($seen, array_unique($seen), 'No item may appear twice across the walk');
        // Every original item after the first page, plus the appended one, is seen
        self::assertSame([...range(1, 15), 99], array_values(array_intersect($seen, [...range(1, 15), 99])));
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function makeSource(int $count): array
    {
        $source = [];
        for ($i = 1; $i <= $count; ++$i) {
            $source[] = ['id' => $i, 'name' => 'Item '.$i];
        }

        return $source;
    }

    /**
     * @param list<array<string, mixed>> $source
     * @param string|list<string>        $field
     *
     * @return list<array{items: list<mixed>, nextCursor: ?string, previousCursor: ?string, hasMore: bool}>
     */
    private function walkForward(array $source, int $perPage, string|array $field, string $direction): array
    {
        $pages = [];
        $cursor = null;
        $guard = 0;
        $order = CursorOrder::byFields((array) $field, $direction);
        do {
            $page = $this->adapter->sliceWithCursor($source, $cursor, $perPage, $order);
            $pages[] = $page;
            $cursor = $page->next;
        } while (null !== $cursor && ++$guard < 50);

        return $pages;
    }

    /**
     * @param list<array{items: list<mixed>}> $pages
     *
     * @return list<int>
     */
    private function collectIds(array $pages): array
    {
        $ids = [];
        foreach ($pages as $page) {
            $ids = [...$ids, ...array_column($page->items, 'id')];
        }

        return $ids;
    }
}
