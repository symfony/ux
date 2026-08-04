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

use Symfony\UX\Pagination\Adapter\CursorAdapterInterface;
use Symfony\UX\Pagination\Cursor\CursorBoundary;
use Symfony\UX\Pagination\Cursor\CursorCodecInterface;
use Symfony\UX\Pagination\Cursor\CursorOrder;
use Symfony\UX\Pagination\Exception\InvalidArgumentException;
use Symfony\UX\Pagination\Exception\InvalidCursorException;
use Symfony\UX\Pagination\Navigation\PaginationUrlGenerator;

/**
 * Cursor-based pagination result.
 *
 * Unlike offset-based Pagination, this uses opaque cursors for navigation.
 * There are no page numbers, no total count, and no random page access.
 * Just next/previous -- ideal for large datasets, feeds, or APIs.
 *
 * @template T
 *
 * @implements CursorPaginationInterface<T>
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class CursorPagination implements CursorPaginationInterface
{
    /** @var list<T>|null */
    private ?array $cachedItems = null;

    private ?string $cachedNextCursor = null;

    private ?string $cachedPreviousCursor = null;

    private ?bool $cachedHasNext = null;

    private bool $fetched = false;

    /**
     * @internal use PaginatorInterface::cursor() to create instances
     *
     * @param list<T>|null        $items    Preloaded items for internal result mapping
     * @param CursorBoundary|null $boundary Already-validated boundary; when null, the cursor is decoded on first fetch
     */
    public function __construct(
        private readonly mixed $source,
        private readonly CursorAdapterInterface $adapter,
        private readonly ?string $cursor,
        /** @param int<1, max> $perPage */
        private readonly int $perPage,
        private readonly CursorOrder $order,
        private readonly CursorCodecInterface $cursorCodec,
        private readonly string $context,
        private readonly PaginationUrlGenerator $paginationUrlGenerator,
        private ?PaginationInfoFormatter $infoFormatter = null,
        ?array $items = null,
        private readonly ?CursorBoundary $boundary = null,
    ) {
        if ($perPage < 1) {
            throw new InvalidArgumentException('perPage must be >= 1.');
        }
        if (\PHP_INT_MAX === $perPage) {
            throw new InvalidArgumentException('perPage must be less than PHP_INT_MAX.');
        }

        $this->cachedItems = $items;
        $this->fetched = null !== $items;
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
        $this->ensureFetched();

        return $this->cachedItems ?? [];
    }

    /**
     * Number of items on THIS page, not a total item count.
     *
     * Cursor pagination never exposes a total.
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

    public function getItemsPerPage(): int
    {
        return $this->perPage;
    }

    /**
     * The cursor used for this page. Null for the first page.
     */
    public function getCursor(): ?string
    {
        return $this->cursor;
    }

    public function isEmpty(): bool
    {
        return 0 === $this->count();
    }

    public function hasNext(): bool
    {
        $this->ensureFetched();

        return $this->cachedHasNext ?? false;
    }

    public function hasPrevious(): bool
    {
        return null !== $this->getPreviousCursor();
    }

    /**
     * Cursor for fetching the next page.
     */
    public function getNextCursor(): ?string
    {
        $this->ensureFetched();

        return $this->cachedNextCursor;
    }

    /**
     * Cursor for fetching the previous page.
     */
    public function getPreviousCursor(): ?string
    {
        $this->ensureFetched();

        return $this->cachedPreviousCursor;
    }

    public function getNextUrl(): ?string
    {
        $next = $this->getNextCursor();
        if (null === $next) {
            return null;
        }

        return $this->getCursorUrl($next);
    }

    public function getPreviousUrl(): ?string
    {
        $previous = $this->getPreviousCursor();
        if (null === $previous) {
            return null;
        }

        return $this->getCursorUrl($previous);
    }

    /**
     * Generate a URL with the given cursor value.
     */
    public function getCursorUrl(string $cursor): string
    {
        return $this->paginationUrlGenerator->getCursorUrl($cursor);
    }

    /**
     * @return array{prev: string|null, next: string|null}
     */
    public function getLinks(): array
    {
        return [
            'prev' => $this->getPreviousUrl(),
            'next' => $this->getNextUrl(),
        ];
    }

    /**
     * @return array{
     *     items: list<T>,
     *     per_page: int,
     *     cursor: string|null,
     *     next_cursor: string|null,
     *     previous_cursor: string|null,
     *     has_next: bool,
     *     has_previous: bool,
     *     links: array{prev: string|null, next: string|null}
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'items' => $this->getItems(),
            'per_page' => $this->perPage,
            'cursor' => $this->cursor,
            'next_cursor' => $this->getNextCursor(),
            'previous_cursor' => $this->getPreviousCursor(),
            'has_next' => $this->hasNext(),
            'has_previous' => $this->hasPrevious(),
            'links' => $this->getLinks(),
        ];
    }

    public function getInfo(): string
    {
        return ($this->infoFormatter ??= new PaginationInfoFormatter())->formatCursor($this);
    }

    private function ensureFetched(): void
    {
        if ($this->fetched) {
            return;
        }

        $orderFingerprint = $this->order->getFingerprint();
        $boundary = $this->boundary;
        if (null === $boundary && null !== $this->cursor) {
            try {
                $decoded = $this->cursorCodec->decode($this->cursor, $orderFingerprint, $this->context);
            } catch (\InvalidArgumentException $exception) {
                throw new InvalidCursorException($exception->getMessage(), $exception);
            }
            $boundary = new CursorBoundary($decoded['values'], $decoded['forward']);
        }

        $result = $this->adapter->sliceWithCursor(
            $this->source,
            $boundary,
            $this->perPage,
            $this->order,
        );

        $this->cachedItems = $result->items;
        $this->cachedNextCursor = null !== $result->next
            ? $this->cursorCodec->encode($result->next->values, $result->next->forward, $orderFingerprint, $this->context)
            : null;
        $this->cachedPreviousCursor = null !== $result->previous
            ? $this->cursorCodec->encode($result->previous->values, $result->previous->forward, $orderFingerprint, $this->context)
            : null;
        $this->cachedHasNext = $result->hasNext;
        $this->fetched = true;
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
            cursor: $this->cursor,
            perPage: $this->perPage,
            order: $this->order,
            cursorCodec: $this->cursorCodec,
            context: $this->context,
            paginationUrlGenerator: $this->paginationUrlGenerator,
            infoFormatter: $this->infoFormatter,
            items: $items,
            boundary: $this->boundary,
        );
        $pagination->cachedNextCursor = $this->cachedNextCursor;
        $pagination->cachedPreviousCursor = $this->cachedPreviousCursor;
        $pagination->cachedHasNext = $this->cachedHasNext;

        return $pagination;
    }
}
