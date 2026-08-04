<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Adapter;

use Symfony\UX\Pagination\Cursor\CursorBoundary;
use Symfony\UX\Pagination\Cursor\CursorOrder;
use Symfony\UX\Pagination\Cursor\CursorSlice;

/**
 * Adapter capable of cursor-based pagination.
 *
 * Cursor pagination uses an opaque cursor value (typically an encoded
 * field value like an ID or timestamp) to fetch the next/previous page,
 * instead of a numeric offset. This avoids the performance issues of
 * OFFSET-based pagination on large datasets.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
interface CursorAdapterInterface extends PaginationAdapterInterface
{
    /**
     * Resolve the effective order used to fetch and sign cursor boundaries.
     *
     * Field-based adapters validate the requested fields and may append a
     * unique tie-breaker. Adapters for remote cursor APIs may own their order
     * and return an opaque identity when no fields were requested.
     *
     * @param list<string>|null $fields
     */
    public function resolveCursorOrder(mixed $source, ?array $fields, ?string $direction): CursorOrder;

    /**
     * Return the stable source context bound to signed cursor tokens.
     *
     * The optional application context can scope otherwise identical sources
     * by tenant, user or business query.
     */
    public function getCursorContext(mixed $source, ?string $context): string;

    /**
     * Fetch items after (or before) the given cursor.
     *
     * Returns the items plus navigation cursors for the next/previous pages.
     *
     * @return CursorSlice<mixed>
     */
    public function sliceWithCursor(
        mixed $source,
        ?CursorBoundary $boundary,
        int $limit,
        CursorOrder $order,
    ): CursorSlice;
}
