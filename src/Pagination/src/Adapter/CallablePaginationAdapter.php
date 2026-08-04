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

use Symfony\UX\Pagination\Exception\RuntimeException;

/**
 * Adapter for callable data sources.
 *
 * Wraps user-provided callbacks for slicing and counting.
 * Unlike other adapters, this one is instantiated per-use (not auto-discovered),
 * because it holds closure state.
 *
 * @internal
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class CallablePaginationAdapter implements OffsetAdapterInterface, LookaheadAdapterInterface
{
    /**
     * @param \Closure(int, int): array<array-key, mixed> $slicer  fn($offset, $limit) => items
     * @param \Closure(): int                             $counter fn() => total count
     */
    public function __construct(
        private readonly \Closure $slicer,
        private readonly \Closure $counter,
    ) {
    }

    public function supports(mixed $source): bool
    {
        // CallablePaginationAdapter is never auto-discovered.
        // It is instantiated directly by the Paginator service.
        return false;
    }

    public function slice(mixed $source, int $offset, int $limit): array
    {
        $result = ($this->slicer)($offset, $limit);

        if (!\is_array($result)) {
            throw new RuntimeException('Slicer callback must return an array.');
        }

        return $result;
    }

    public function count(mixed $source): int
    {
        $count = ($this->counter)();
        if (!\is_int($count) || $count < 0) {
            throw new RuntimeException(\sprintf('Counter callback must return a non-negative integer, got "%s".', get_debug_type($count)));
        }

        return $count;
    }

    public function sliceWithLookahead(mixed $source, int $offset, int $limit): array
    {
        $items = ($this->slicer)($offset, $limit + 1);

        if (!\is_array($items)) {
            throw new RuntimeException('Slicer callback must return an array.');
        }

        $hasMore = \count($items) > $limit;

        if ($hasMore) {
            array_pop($items);
        }

        return [array_values($items), $hasMore];
    }
}
