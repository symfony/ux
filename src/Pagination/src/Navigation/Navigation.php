<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Navigation;

use Symfony\UX\Pagination\Exception\InvalidArgumentException;
use Symfony\UX\Pagination\Exception\NavigationTooLargeException;

/**
 * Composable page range iterable for rendering pagination controls.
 *
 * Generates PageLink objects based on the current page, total pages,
 * and navigation mode (sliding, fixed, full).
 *
 * @implements \IteratorAggregate<int, PageLink>
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class Navigation implements \IteratorAggregate, \Countable
{
    public function __construct(
        /** @param int<1, max> $currentPage */
        private readonly int $currentPage,
        /** @param int<1, max>|null $totalPages */
        private readonly ?int $totalPages,
        private readonly PaginationUrlGenerator $paginationUrlGenerator,
        private readonly NavigationMode $mode = NavigationMode::Sliding,
        /** @param int<1, max> $modeParameter */
        private readonly int $modeParameter = 5,
    ) {
        if ($modeParameter < 1) {
            throw new InvalidArgumentException('Navigation size must be >= 1.');
        }
    }

    /**
     * @return \Traversable<int, PageLink>
     */
    public function getIterator(): \Traversable
    {
        if (null === $this->totalPages || $this->totalPages < 1) {
            return;
        }

        $this->validateFullNavigationSize();

        $links = match ($this->mode) {
            NavigationMode::Sliding => $this->slidingLinks(),
            NavigationMode::Fixed => $this->fixedWindow(),
            NavigationMode::Full => $this->fullWindow(),
        };

        yield from $links;
    }

    /**
     * @return int<0, max>
     */
    public function count(): int
    {
        if (null === $this->totalPages || $this->totalPages < 1) {
            return 0;
        }

        $this->validateFullNavigationSize();

        return max(0, match ($this->mode) {
            NavigationMode::Full => $this->totalPages,
            NavigationMode::Sliding => $this->countSliding(),
            NavigationMode::Fixed => $this->countFixed(),
        });
    }

    private function validateFullNavigationSize(): void
    {
        if (NavigationMode::Full === $this->mode && null !== $this->totalPages && $this->totalPages > $this->modeParameter) {
            throw new NavigationTooLargeException($this->totalPages, $this->modeParameter);
        }
    }

    private function countSliding(): int
    {
        $window = $this->slidingWindow();

        return ($window['end'] - $window['start'] + 1)
            + (int) $window['first'] + (int) $window['firstGap']
            + (int) $window['last'] + (int) $window['lastGap'];
    }

    private function countFixed(): int
    {
        \assert(null !== $this->totalPages);
        $blocks = $this->fixedBlocks();

        $count = $blocks['blockEnd'] - $blocks['blockStart'] + 1;

        if ($blocks['showFirst']) {
            $count += $blocks['firstBlockEnd'] + (int) $blocks['firstGap'];
        }

        if ($blocks['showLast']) {
            $count += $this->totalPages - $blocks['lastBlockStart'] + 1 + (int) $blocks['lastGap'];
        }

        return $count;
    }

    /**
     * The sliding window bounds, shared by iteration and counting.
     *
     * @return array{start: int, end: int, first: bool, firstGap: bool, last: bool, lastGap: bool}
     */
    private function slidingWindow(): array
    {
        \assert(null !== $this->totalPages);
        $lastPage = $this->totalPages;
        $size = $this->modeParameter;
        $before = intdiv($size - 1, 2);
        $after = $size - 1 - $before;

        $rangeStart = max(1, $this->currentPage - $before);
        $rangeEnd = min($lastPage, $this->currentPage + $after);

        // Expand range if near edges
        if (1 === $rangeStart) {
            $rangeEnd = min($lastPage, $size);
        }
        if ($rangeEnd === $lastPage) {
            $rangeStart = max(1, $lastPage - $size + 1);
        }

        return [
            'start' => $rangeStart,
            'end' => $rangeEnd,
            'first' => $rangeStart > 1,
            'firstGap' => $rangeStart > 2,
            'last' => $rangeEnd < $lastPage,
            'lastGap' => $rangeEnd < $lastPage - 1,
        ];
    }

    /**
     * The fixed-block bounds, shared by iteration and counting.
     *
     * @return array{blockStart: int, blockEnd: int, showFirst: bool, firstBlockEnd: int, firstGap: bool, showLast: bool, lastBlockStart: int, lastGap: bool}
     */
    private function fixedBlocks(): array
    {
        \assert(null !== $this->totalPages);
        $lastPage = $this->totalPages;
        $blockSize = $this->modeParameter;

        $currentBlock = (int) ceil($this->currentPage / $blockSize);
        $totalBlocks = (int) ceil($lastPage / $blockSize);

        $blockStart = ($currentBlock - 1) * $blockSize + 1;
        $blockEnd = min($lastPage, $currentBlock * $blockSize);
        $lastBlockStart = max($blockEnd + 1, ($totalBlocks - 1) * $blockSize + 1);

        return [
            'blockStart' => $blockStart,
            'blockEnd' => $blockEnd,
            'showFirst' => $blockStart > 1,
            'firstBlockEnd' => min($lastPage, $blockSize),
            'firstGap' => $blockStart > $blockSize + 1,
            'showLast' => $blockEnd < $lastPage,
            'lastBlockStart' => $lastBlockStart,
            'lastGap' => $lastBlockStart > $blockEnd + 1,
        ];
    }

    /**
     * @return iterable<int, PageLink>
     */
    private function slidingLinks(): iterable
    {
        $window = $this->slidingWindow();

        // First page + ellipsis
        if ($window['first']) {
            yield $this->pageLink(1);

            if ($window['firstGap']) {
                yield $this->gap();
            }
        }

        // Main range
        for ($page = $window['start']; $page <= $window['end']; ++$page) {
            yield $this->pageLink($page);
        }

        // Ellipsis + last page
        if ($window['last']) {
            if ($window['lastGap']) {
                yield $this->gap();
            }

            \assert(null !== $this->totalPages);
            yield $this->pageLink($this->totalPages);
        }
    }

    /**
     * @return iterable<int, PageLink>
     */
    private function fixedWindow(): iterable
    {
        \assert(null !== $this->totalPages);
        $blocks = $this->fixedBlocks();

        // First block
        if ($blocks['showFirst']) {
            for ($page = 1; $page <= $blocks['firstBlockEnd']; ++$page) {
                yield $this->pageLink($page);
            }

            if ($blocks['firstGap']) {
                yield $this->gap();
            }
        }

        // Current block
        for ($page = $blocks['blockStart']; $page <= $blocks['blockEnd']; ++$page) {
            yield $this->pageLink($page);
        }

        // Last block
        if ($blocks['showLast']) {
            if ($blocks['lastGap']) {
                yield $this->gap();
            }

            for ($page = $blocks['lastBlockStart']; $page <= $this->totalPages; ++$page) {
                yield $this->pageLink($page);
            }
        }
    }

    /**
     * @return iterable<int, PageLink>
     */
    private function fullWindow(): iterable
    {
        \assert(null !== $this->totalPages);

        for ($page = 1; $page <= $this->totalPages; ++$page) {
            yield $this->pageLink($page);
        }
    }

    private function pageLink(int $page): PageLink
    {
        return new PageLink(
            page: $page,
            url: $this->paginationUrlGenerator->getUrl($page),
            isCurrent: $page === $this->currentPage,
            isGap: false,
        );
    }

    private function gap(): PageLink
    {
        return new PageLink(
            page: 0,
            url: '',
            isCurrent: false,
            isGap: true,
        );
    }
}
