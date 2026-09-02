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
 * Common contract of pagination results.
 *
 * Implemented by both Pagination (offset-based) and CursorPagination
 * (cursor-based): consumers that iterate items, test navigation state,
 * build previous/next links, or display a summary do not need to know which
 * strategy produced the result.
 *
 * Configuration and item transformation deliberately stay out of this
 * contract. Configure URLs and policies on the builder before calling
 * paginate(); concrete result classes may expose additional conveniences.
 *
 * @template T
 *
 * @extends \IteratorAggregate<array-key, T>
 *
 * @author Simon André <smn.andre@gmail.com>
 */
interface PaginationInterface extends \Countable, \IteratorAggregate, \JsonSerializable
{
    /**
     * Get all items on the current page.
     *
     * @return list<T>
     */
    public function getItems(): array;

    public function isEmpty(): bool;

    public function getItemsPerPage(): int;

    public function hasNext(): bool;

    public function hasPrevious(): bool;

    public function getNextUrl(): ?string;

    public function getPreviousUrl(): ?string;

    /**
     * Get a human-readable summary like "Showing 1-20 of 100".
     */
    public function getInfo(): string;
}
