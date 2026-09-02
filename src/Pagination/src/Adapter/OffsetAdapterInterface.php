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

/**
 * Adapter capable of offset pagination with an exact total.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
interface OffsetAdapterInterface extends PaginationAdapterInterface
{
    /**
     * Fetch a slice of items from the source.
     *
     * @return array<array-key, mixed>
     */
    public function slice(mixed $source, int $offset, int $limit): array;

    /**
     * Count all items matching the source.
     */
    public function count(mixed $source): int;
}
