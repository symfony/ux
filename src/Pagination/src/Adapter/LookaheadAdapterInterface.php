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
 * Adapter capable of N+1 lookahead pagination.
 *
 * Fetches one extra item to determine if more pages exist,
 * avoiding the need for a COUNT(*) query.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
interface LookaheadAdapterInterface extends PaginationAdapterInterface
{
    /**
     * Fetch items with one extra to detect if more exist.
     *
     * @return array{list<mixed>, bool} Items (capped at $limit) and hasMore flag
     */
    public function sliceWithLookahead(mixed $source, int $offset, int $limit): array;
}
