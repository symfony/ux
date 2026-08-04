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
 * Entry point for numbered and cursor pagination.
 *
 * Type-hint this interface in your controller to get an auto-wired
 * paginator service.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
interface PaginatorInterface
{
    /**
     * Paginate any supported data source.
     *
     * When $page is null, the current page is automatically read from the
     * current Request. A route attribute has priority over the query parameter
     * (default: "page"). Passing a page explicitly skips RequestStack entirely.
     * This removes the most common boilerplate from controllers:
     *
     *     // Before: $paginator->paginate($qb, $request->query->getInt('page', 1));
     *     // After:  $paginator->paginate($qb);
     *
     * @template T
     *
     * @param array<T>|mixed $source  A QueryBuilder, array, or any source supported by a registered adapter
     * @param int|null       $page    Current page number (1-based). Null = resolve from the current Request, then default to 1.
     * @param int|null       $perPage Items per page. Null = use configured default (items_per_page).
     *
     * @return ($source is array<T> ? NumberedPaginationInterface<T> : NumberedPaginationInterface<mixed>)
     */
    public function paginate(mixed $source, ?int $page = null, ?int $perPage = null): NumberedPaginationInterface;

    /**
     * Start configuring numbered pagination backed by slicing and counting callbacks.
     *
     * Useful for HTTP APIs, raw SQL, or any source not covered by a
     * registered adapter.
     *
     * @template T
     *
     * @param callable(int, int): list<T> $slice fn($offset, $limit) => items
     * @param callable(): int             $count fn() => total count
     */
    public function fromCallbacks(callable $slice, callable $count): PaginationBuilder;

    /**
     * Start building a paginated query with advanced options.
     */
    public function query(mixed $source): PaginationBuilder;

    /**
     * Start configuring cursor pagination for a supported source.
     *
     * Field-based adapters require a stable total order through
     * CursorPaginationBuilder::orderBy(). A remote cursor adapter may own its
     * order and make that step optional.
     */
    public function cursor(mixed $source): CursorPaginationBuilder;
}
