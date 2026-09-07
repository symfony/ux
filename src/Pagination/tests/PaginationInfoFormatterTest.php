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
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Pagination\Adapter\ArrayPaginationAdapter;
use Symfony\UX\Pagination\Adapter\CursorAdapterInterface;
use Symfony\UX\Pagination\Cursor\CursorCodec;
use Symfony\UX\Pagination\CursorPagination;
use Symfony\UX\Pagination\Navigation\PaginationUrlGenerator;
use Symfony\UX\Pagination\Pagination;
use Symfony\UX\Pagination\PaginationInfoFormatter;

#[CoversClass(PaginationInfoFormatter::class)]
final class PaginationInfoFormatterTest extends TestCase
{
    public function testEnglishFallbackForEmptyNumberedPaginationWithTotal(): void
    {
        $formatter = new PaginationInfoFormatter();
        $pagination = $this->createPagination([], 1, 10);

        self::assertSame('No items', $formatter->format($pagination));
    }

    public function testEnglishFallbackForEmptyCursorPagination(): void
    {
        $formatter = new PaginationInfoFormatter();

        self::assertSame('No items', $formatter->formatCursor($this->createCursorPagination([], false)));
    }

    public function testEnglishFallbackForNumberedPaginationWithTotal(): void
    {
        $formatter = new PaginationInfoFormatter();

        self::assertSame('Showing 11-20 of 30', $formatter->format($this->createPagination(range(1, 30), 2, 10)));
    }

    public function testEnglishFallbackForNumberedPaginationWithoutTotal(): void
    {
        $formatter = new PaginationInfoFormatter();
        $pagination = new Pagination(
            source: range(1, 30),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 2,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(queryParam: 'page'),
            lookahead: true,
        );

        self::assertSame('Showing 11-20', $formatter->format($pagination));
    }

    public function testEnglishFallbackForCursorPaginationWithMore(): void
    {
        $formatter = new PaginationInfoFormatter();

        self::assertSame(
            'Showing 3 items',
            $formatter->formatCursor($this->createCursorPagination([1, 2, 3], true)),
        );
    }

    public function testEnglishFallbackPluralizesASingleItem(): void
    {
        $formatter = new PaginationInfoFormatter();

        self::assertSame(
            'Showing 1 item',
            $formatter->formatCursor($this->createCursorPagination([1], true)),
        );
        self::assertSame(
            'Showing 1 item (last page)',
            $formatter->formatCursor($this->createCursorPagination([1], false)),
        );
    }

    public function testEnglishFallbackForLastCursorPage(): void
    {
        $formatter = new PaginationInfoFormatter();

        self::assertSame(
            'Showing 3 items (last page)',
            $formatter->formatCursor($this->createCursorPagination([1, 2, 3], false)),
        );
    }

    public function testFormatWithTotal(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::once())
            ->method('trans')
            ->with(
                'Showing %start%-%end% of %total%',
                ['%start%' => 11, '%end%' => 20, '%total%' => 100],
                'UXPaginationBundle'
            )
            ->willReturn('Showing 11-20 of 100');

        $formatter = new PaginationInfoFormatter($translator);
        $pagination = $this->createPagination(range(1, 100), 2, 10);

        $result = $formatter->format($pagination);

        self::assertSame('Showing 11-20 of 100', $result);
    }

    public function testFormatWithoutTotal(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::once())
            ->method('trans')
            ->with(
                'Showing %start%-%end%',
                ['%start%' => 11, '%end%' => 20],
                'UXPaginationBundle'
            )
            ->willReturn('Showing 11-20');

        $formatter = new PaginationInfoFormatter($translator);
        $pagination = new Pagination(
            source: range(1, 100),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 2,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(queryParam: 'page'),
            lookahead: true,
        );

        $result = $formatter->format($pagination);

        self::assertSame('Showing 11-20', $result);
    }

    public function testFormatFirstPage(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::once())
            ->method('trans')
            ->with(
                'Showing %start%-%end% of %total%',
                ['%start%' => 1, '%end%' => 10, '%total%' => 50],
                'UXPaginationBundle'
            )
            ->willReturn('Showing 1-10 of 50');

        $formatter = new PaginationInfoFormatter($translator);
        $pagination = $this->createPagination(range(1, 50), 1, 10);

        $result = $formatter->format($pagination);

        self::assertSame('Showing 1-10 of 50', $result);
    }

    public function testFormatLastPage(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::once())
            ->method('trans')
            ->with(
                'Showing %start%-%end% of %total%',
                ['%start%' => 21, '%end%' => 25, '%total%' => 25],
                'UXPaginationBundle'
            )
            ->willReturn('Showing 21-25 of 25');

        $formatter = new PaginationInfoFormatter($translator);
        $pagination = $this->createPagination(range(1, 25), 3, 10);

        $result = $formatter->format($pagination);

        self::assertSame('Showing 21-25 of 25', $result);
    }

    public function testFormatCursorNoItems(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::once())
            ->method('trans')
            ->with(
                'No items',
                [],
                'UXPaginationBundle'
            )
            ->willReturn('No items');

        $formatter = new PaginationInfoFormatter($translator);
        $pagination = $this->createCursorPagination([], false);

        $result = $formatter->formatCursor($pagination);

        self::assertSame('No items', $result);
    }

    public function testFormatCursorWithMore(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::once())
            ->method('trans')
            ->with(
                'Showing %count% item|Showing %count% items',
                ['%count%' => 10],
                'UXPaginationBundle'
            )
            ->willReturn('Showing 10 items');

        $formatter = new PaginationInfoFormatter($translator);
        $pagination = $this->createCursorPagination(range(1, 10), true);

        $result = $formatter->formatCursor($pagination);

        self::assertSame('Showing 10 items', $result);
    }

    public function testFormatNoItems(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::once())
            ->method('trans')
            ->with(
                'No items',
                [],
                'UXPaginationBundle'
            )
            ->willReturn('No results');

        $formatter = new PaginationInfoFormatter($translator);
        // Page 5 of 3 pages = 0 items on this page
        $pagination = $this->createPagination(range(1, 30), 5, 10);

        $result = $formatter->format($pagination);

        self::assertSame('No results', $result);
    }

    public function testFormatCursorLastPage(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::once())
            ->method('trans')
            ->with(
                'Showing %count% item (last page)|Showing %count% items (last page)',
                ['%count%' => 5],
                'UXPaginationBundle'
            )
            ->willReturn('Showing 5 items (last page)');

        $formatter = new PaginationInfoFormatter($translator);
        $pagination = $this->createCursorPagination(range(1, 5), false);

        $result = $formatter->formatCursor($pagination);

        self::assertSame('Showing 5 items (last page)', $result);
    }

    /**
     * @param array<int, mixed> $data
     */
    private function createPagination(array $data, int $page, int $perPage): Pagination
    {
        return new Pagination(
            source: $data,
            adapter: new ArrayPaginationAdapter(),
            currentPage: $page,
            perPage: $perPage,
            paginationUrlGenerator: new PaginationUrlGenerator(queryParam: 'page'),
        );
    }

    /**
     * @param array<int, mixed> $items
     */
    private function createCursorPagination(array $items, bool $hasMore): CursorPagination
    {
        $adapter = $this->createStub(CursorAdapterInterface::class);
        $adapter->method('sliceWithCursor')->willReturn(new \Symfony\UX\Pagination\Cursor\CursorSlice(
            $items,
            $hasMore ? new \Symfony\UX\Pagination\Cursor\CursorBoundary([10]) : null,
            null,
            $hasMore,
        ));

        return new CursorPagination(
            source: $items,
            adapter: $adapter,
            cursor: null,
            perPage: 10,
            order: \Symfony\UX\Pagination\Cursor\CursorOrder::byFields(['id'], 'ASC'),
            cursorCodec: new CursorCodec('test-secret'),
            context: 'test',
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
        );
    }
}
