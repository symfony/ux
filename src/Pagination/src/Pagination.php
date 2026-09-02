<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination;

use Symfony\UX\Pagination\Adapter\LookaheadAdapterInterface;
use Symfony\UX\Pagination\Adapter\OffsetAdapterInterface;
use Symfony\UX\Pagination\Adapter\PaginationAdapterInterface;
use Symfony\UX\Pagination\Exception\InvalidArgumentException;
use Symfony\UX\Pagination\Exception\OffsetLimitExceededException;
use Symfony\UX\Pagination\Exception\OutOfRangePageException;
use Symfony\UX\Pagination\Navigation\Navigation;
use Symfony\UX\Pagination\Navigation\NavigationMode;
use Symfony\UX\Pagination\Navigation\PaginationUrlGenerator;

/**
 * Numbered pagination result.
 *
 * Holds the current slice and its navigation state.
 *
 * All queries are lazy: nothing executes until you access items, count, or navigation.
 *
 * @template T
 *
 * @implements NumberedPaginationInterface<T>
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class Pagination implements NumberedPaginationInterface
{
    /** @var list<T>|null */
    private ?array $cachedItems = null;

    private ?int $cachedCount = null;

    private ?bool $cachedHasMore = null;

    private readonly int $offset;

    /**
     * @internal use PaginatorInterface::paginate() or PaginationBuilder to create instances
     *
     * @param list<T>|null $items Preloaded items for internal result mapping
     */
    public function __construct(
        private readonly mixed $source,
        private readonly PaginationAdapterInterface $adapter,
        private readonly int $currentPage,
        private readonly int $perPage,
        private readonly PaginationUrlGenerator $paginationUrlGenerator,
        private readonly NavigationMode $navigationMode = NavigationMode::Sliding,
        private readonly int $navigationModeParameter = 5,
        private readonly bool $lookahead = false,
        private readonly int|\Closure|null $total = null,
        private ?PaginationInfoFormatter $infoFormatter = null,
        private readonly int $maxOffset = 100_000,
        ?array $items = null,
    ) {
        if ($currentPage < 1) {
            throw new InvalidArgumentException('currentPage must be >= 1.');
        }
        if (null !== $total && \is_int($total) && $total < 0) {
            throw new InvalidArgumentException('Pagination total must be greater than or equal to 0.');
        }
        if ($perPage < 1) {
            throw new InvalidArgumentException('perPage must be >= 1.');
        }
        if (\PHP_INT_MAX === $perPage) {
            throw new InvalidArgumentException('perPage must be less than PHP_INT_MAX.');
        }
        if ($maxOffset < 0) {
            throw new InvalidArgumentException('maxOffset must be >= 0.');
        }
        if ($lookahead && !$adapter instanceof LookaheadAdapterInterface) {
            throw new InvalidArgumentException(\sprintf('Lookahead pagination requires an adapter implementing "%s".', LookaheadAdapterInterface::class));
        }
        if (!$lookahead && !$adapter instanceof OffsetAdapterInterface) {
            throw new InvalidArgumentException(\sprintf('Offset pagination requires an adapter implementing "%s".', OffsetAdapterInterface::class));
        }

        $pageIndex = $currentPage - 1;
        if ($pageIndex > intdiv(\PHP_INT_MAX, $perPage)) {
            throw new OffsetLimitExceededException($currentPage, $perPage, $maxOffset);
        }

        $this->offset = $pageIndex * $perPage;
        $this->cachedItems = $items;
        if ($this->offset > $maxOffset) {
            throw new OffsetLimitExceededException($currentPage, $perPage, $maxOffset);
        }
    }

    /**
     * @return \Traversable<array-key, T>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->getItems());
    }

    /**
     * Get all items on the current page.
     *
     * @return list<T>
     */
    public function getItems(): array
    {
        return $this->cachedItems ??= $this->fetchItems();
    }

    /**
     * Number of items on THIS page, not the total item count.
     *
     * This differs from paginators where count() returns the total:
     * use getTotalItems() for the total.
     *
     * @return int<0, max>
     */
    public function count(): int
    {
        return \count($this->getItems());
    }

    /**
     * Return a copy with each item transformed.
     *
     * This convenience is intentionally not part of PaginationInterface:
     * application-wide transformations belong in repositories, DTO mappers,
     * or serializers.
     *
     * @template U
     *
     * @param callable(T): U $fn
     *
     * @return self<U>
     */
    public function map(callable $fn): self
    {
        return $this->withItems(array_map($fn, $this->getItems()));
    }

    /**
     * @return int<1, max>
     */
    public function getCurrentPage(): int
    {
        \assert($this->currentPage >= 1);

        return $this->currentPage;
    }

    public function getItemsPerPage(): int
    {
        return $this->perPage;
    }

    /**
     * @return int<1, max>|null
     */
    public function getFirstItemNumber(): ?int
    {
        if (0 === $this->count()) {
            return null;
        }

        $firstItemNumber = ($this->currentPage - 1) * $this->perPage + 1;
        \assert($firstItemNumber >= 1);

        return $firstItemNumber;
    }

    /**
     * @return int<1, max>|null
     */
    public function getLastItemNumber(): ?int
    {
        $first = $this->getFirstItemNumber();
        if (null === $first) {
            return null;
        }

        $lastItemNumber = $first + $this->count() - 1;
        \assert($lastItemNumber >= 1);

        return $lastItemNumber;
    }

    /**
     * The URL query parameter name used for page numbers (e.g. "page").
     */
    public function getPageParameterName(): string
    {
        return $this->paginationUrlGenerator->getQueryParameterName();
    }

    /**
     * Total number of items across all pages.
     * Returns null in lookahead mode.
     *
     * @return int<0, max>|null
     */
    public function getTotalItems(): ?int
    {
        if ($this->lookahead) {
            return null;
        }

        if (null !== $this->cachedCount) {
            $total = $this->cachedCount;
            \assert($total >= 0);

            return $total;
        }

        if (\is_int($this->total)) {
            $total = $this->validateCount($this->total);
            $this->cachedCount = $total;

            return $total;
        }

        if ($this->total instanceof \Closure) {
            $total = ($this->total)();
            if (!\is_int($total)) {
                throw new InvalidArgumentException(\sprintf('Total callable must return an int, got "%s".', get_debug_type($total)));
            }

            $total = $this->validateCount($total);
            $this->cachedCount = $total;

            return $total;
        }

        /** @var OffsetAdapterInterface $adapter Guaranteed by the constructor outside lookahead mode. */
        $adapter = $this->adapter;

        $total = $this->validateCount($adapter->count($this->source));
        $this->cachedCount = $total;

        return $total;
    }

    /**
     * Total number of pages.
     * Returns null in lookahead mode.
     *
     * @return int<1, max>|null
     */
    public function getTotalPages(): ?int
    {
        $total = $this->getTotalItems();
        if (null === $total) {
            return null;
        }

        if (0 === $total) {
            return 1;
        }

        $totalPages = intdiv($total, $this->perPage) + (int) (0 !== $total % $this->perPage);
        \assert($totalPages >= 1);

        return $totalPages;
    }

    public function isEmpty(): bool
    {
        return 0 === $this->count();
    }

    public function hasNext(): bool
    {
        if ($this->lookahead) {
            if (null === $this->cachedHasMore) {
                // Force item fetch, which also resolves the lookahead flag
                $this->getItems();
            }

            return $this->cachedHasMore ?? false;
        }

        $totalPages = $this->getTotalPages();

        return $this->currentPage < $totalPages;
    }

    public function hasPrevious(): bool
    {
        return $this->currentPage > 1;
    }

    public function isFirst(): bool
    {
        return 1 === $this->currentPage;
    }

    public function isLast(): bool
    {
        return !$this->hasNext();
    }

    /**
     * Whether the current page is beyond the total number of pages.
     * Always false in lookahead mode (total is unknown).
     */
    public function isOutOfRange(): bool
    {
        $totalPages = $this->getTotalPages();
        if (null === $totalPages) {
            return false;
        }

        return $this->currentPage > $totalPages;
    }

    /**
     * Throw a 404 exception if the current page is out of range.
     *
     * Prefer PaginationBuilder::throwOnOutOfRange() when configuring a
     * pagination flow. This result-level convenience is kept for callers
     * working with the concrete class.
     *
     * @return $this
     *
     * @throws OutOfRangePageException
     */
    public function throwOnOutOfRange(): static
    {
        if ($this->isOutOfRange()) {
            throw new OutOfRangePageException($this->currentPage, $this->getTotalPages() ?? 1);
        }

        return $this;
    }

    /**
     * @param int<1, max> $page
     */
    public function getUrl(int $page): string
    {
        return $this->paginationUrlGenerator->getUrl($page);
    }

    /**
     * Absolute URL (scheme and host included) for a page.
     *
     * Useful for feeds, emails, HTTP link headers and application-defined
     * metadata consumed outside the origin.
     */
    public function getAbsoluteUrl(int $page): string
    {
        return $this->paginationUrlGenerator->getAbsoluteUrl($page);
    }

    public function getNextUrl(): ?string
    {
        if (!$this->hasNext()) {
            return null;
        }

        $nextPage = $this->currentPage + 1;
        \assert($nextPage >= 1);

        return $this->getUrl($nextPage);
    }

    public function getPreviousUrl(): ?string
    {
        if (!$this->hasPrevious()) {
            return null;
        }

        $previousPage = $this->currentPage - 1;
        \assert($previousPage >= 1);

        return $this->getUrl($previousPage);
    }

    public function getFirstUrl(): string
    {
        return $this->getUrl(1);
    }

    public function getLastUrl(): ?string
    {
        $totalPages = $this->getTotalPages();
        if (null === $totalPages) {
            return null;
        }

        \assert($totalPages >= 1);

        return $this->getUrl($totalPages);
    }

    public function getPages(): Navigation
    {
        return new Navigation(
            $this->currentPage,
            $this->getTotalPages(),
            $this->paginationUrlGenerator,
            $this->navigationMode,
            $this->navigationModeParameter,
        );
    }

    /**
     * Get pagination metadata without items.
     *
     * Useful for APIs that separate items from metadata (JSON:API, etc.).
     *
     * @return array{current_page: int, per_page: int, has_next: bool, has_previous: bool, total_items?: int, total_pages?: int}
     */
    public function getMetadata(): array
    {
        $meta = [
            'current_page' => $this->currentPage,
            'per_page' => $this->perPage,
            'has_next' => $this->hasNext(),
            'has_previous' => $this->hasPrevious(),
        ];

        $total = $this->getTotalItems();
        if (null !== $total) {
            $meta['total_items'] = $total;
            $meta['total_pages'] = $this->getTotalPages() ?? 1;
        }

        return $meta;
    }

    /**
     * Get pagination navigation URLs.
     *
     * @return array{first: string, last: string|null, prev: string|null, next: string|null}
     */
    public function getLinks(): array
    {
        return [
            'first' => $this->getFirstUrl(),
            'last' => $this->getLastUrl(),
            'prev' => $this->getPreviousUrl(),
            'next' => $this->getNextUrl(),
        ];
    }

    /**
     * @return array{
     *     items: list<T>,
     *     current_page: int,
     *     per_page: int,
     *     has_next: bool,
     *     has_previous: bool,
     *     total_items?: int,
     *     total_pages?: int,
     *     links: array{first: string, last: string|null, prev: string|null, next: string|null}
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'items' => $this->getItems(),
            ...$this->getMetadata(),
            'links' => $this->getLinks(),
        ];
    }

    /**
     * Get a human-readable summary like "Showing 1-20 of 100".
     */
    public function getInfo(): string
    {
        return ($this->infoFormatter ??= new PaginationInfoFormatter())->format($this);
    }

    /**
     * @return list<T>
     */
    private function fetchItems(): array
    {
        if ($this->lookahead) {
            /** @var LookaheadAdapterInterface $adapter Guaranteed by the constructor in lookahead mode. */
            $adapter = $this->adapter;
            [$items, $hasMore] = $adapter->sliceWithLookahead($this->source, $this->offset, $this->perPage);
            $this->cachedHasMore = $hasMore;

            return $items;
        }

        /** @var OffsetAdapterInterface $adapter Guaranteed by the constructor outside lookahead mode. */
        $adapter = $this->adapter;

        return array_values($adapter->slice($this->source, $this->offset, $this->perPage));
    }

    /**
     * @template U
     *
     * @param list<U> $items
     *
     * @return self<U>
     */
    private function withItems(array $items): self
    {
        $pagination = new self(
            source: $this->source,
            adapter: $this->adapter,
            currentPage: $this->currentPage,
            perPage: $this->perPage,
            paginationUrlGenerator: $this->paginationUrlGenerator,
            navigationMode: $this->navigationMode,
            navigationModeParameter: $this->navigationModeParameter,
            lookahead: $this->lookahead,
            total: $this->total,
            infoFormatter: $this->infoFormatter,
            maxOffset: $this->maxOffset,
            items: $items,
        );
        $pagination->cachedCount = $this->cachedCount;
        $pagination->cachedHasMore = $this->cachedHasMore;

        return $pagination;
    }

    /**
     * @return int<0, max>
     */
    private function validateCount(int $count): int
    {
        if ($count < 0) {
            throw new InvalidArgumentException('Pagination count must be greater than or equal to 0.');
        }

        return $count;
    }
}
