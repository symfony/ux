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

/**
 * Contract for cursor pagination results.
 *
 * @template T
 *
 * @extends PaginationInterface<T>
 *
 * @author Simon André <smn.andre@gmail.com>
 */
interface CursorPaginationInterface extends PaginationInterface
{
    /**
     * Return the opaque cursor that produced the current slice.
     */
    public function getCursor(): ?string;

    /**
     * Return the cursor for the next slice, or null at the end.
     */
    public function getNextCursor(): ?string;

    /**
     * Return the cursor for the previous slice, or null at the beginning.
     */
    public function getPreviousCursor(): ?string;

    /**
     * Generate a URL for an opaque cursor value.
     */
    public function getCursorUrl(string $cursor): string;
}
