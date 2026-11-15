<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Query;

/**
 * Applies the sorting and the filters of a data table to its data source.
 *
 * Implement it to plug any data source (an API, a search engine...) into a
 * data table, then pass an instance as the source of the data table.
 *
 * The query is cloned for each data table created by a builder, which then
 * sorts and filters its copy: implement __clone() to copy the objects it changes.
 *
 * @author Simon Daigre <simon@daig.re>
 */
interface QueryInterface
{
    /**
     * Sorts by the given path; called once per sorted column, in order.
     */
    public function sort(string $path, \SortDirection $direction): void;

    public function filter(string $path, Operator $operator, mixed $value): void;

    /**
     * Keeps the rows where at least one of the paths contains the term.
     *
     * @param non-empty-list<string> $paths
     */
    public function search(array $paths, string $term): void;

    /**
     * Returns the sorted and filtered source, paginated by a symfony/ux-pagination adapter.
     */
    public function getSource(): mixed;
}
