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
use Symfony\UX\Pagination\Exception\InvalidArgumentException;
use Symfony\UX\Pagination\Exception\RuntimeException;

/**
 * @internal Built-in adapter. Implement PaginationAdapterInterface for custom sources.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class ArrayPaginationAdapter implements OffsetAdapterInterface, LookaheadAdapterInterface, CursorAdapterInterface
{
    use CursorValuesTrait;

    public function supports(mixed $source): bool
    {
        return \is_array($source);
    }

    public function slice(mixed $source, int $offset, int $limit): array
    {
        if (!\is_array($source)) {
            throw new InvalidArgumentException('Source must be an array.');
        }

        return array_values(\array_slice($source, $offset, $limit));
    }

    public function count(mixed $source): int
    {
        if (!\is_array($source)) {
            throw new InvalidArgumentException('Source must be an array.');
        }

        return \count($source);
    }

    public function getCursorContext(mixed $source, ?string $context): string
    {
        if (!\is_array($source)) {
            throw new InvalidArgumentException('Source must be an array.');
        }
        if (null === $context) {
            throw new RuntimeException('Cursor pagination requires an explicit context() for array sources.');
        }

        return $context;
    }

    /**
     * @param string|list<string> $cursorField
     *
     * @return non-empty-list<string>
     */
    public function resolveCursorFields(mixed $source, string|array $cursorField): array
    {
        if (!\is_array($source)) {
            throw new InvalidArgumentException('Source must be an array.');
        }

        $fields = \is_array($cursorField) ? array_values($cursorField) : [$cursorField];
        if ([] === $fields || array_any($fields, static fn (mixed $field): bool => !\is_string($field) || '' === $field)) {
            throw new InvalidArgumentException('At least one non-empty cursor field is required.');
        }

        return $fields;
    }

    public function resolveCursorOrder(mixed $source, ?array $fields, ?string $direction): CursorOrder
    {
        if (null === $fields || null === $direction) {
            throw new InvalidArgumentException('Array cursor pagination requires an explicit orderBy().');
        }

        return CursorOrder::byFields($this->resolveCursorFields($source, $fields), $direction);
    }

    public function sliceWithLookahead(mixed $source, int $offset, int $limit): array
    {
        if (!\is_array($source)) {
            throw new InvalidArgumentException('Source must be an array.');
        }

        $items = array_values(\array_slice($source, $offset, $limit + 1));
        $hasMore = \count($items) > $limit;

        if ($hasMore) {
            array_pop($items);
        }

        return [$items, $hasMore];
    }

    public function sliceWithCursor(
        mixed $source,
        ?CursorBoundary $boundary,
        int $limit,
        CursorOrder $order,
    ): CursorSlice {
        if (!\is_array($source)) {
            throw new InvalidArgumentException('Source must be an array.');
        }

        $cursorFields = $order->getFields();
        $direction = $order->getDirection();
        if (null === $cursorFields || null === $direction) {
            throw new InvalidArgumentException('Array cursor pagination requires a field-based cursor order.');
        }

        // Sort the source by cursor tuples in display order
        $sorted = array_values($source);
        usort($sorted, function ($a, $b) use ($cursorFields, $direction) {
            $comparison = $this->compareTuples(
                $this->extractCursorValues($a, $cursorFields),
                $this->extractCursorValues($b, $cursorFields),
            );

            return 'ASC' === $direction ? $comparison : -$comparison;
        });

        $tuples = array_map(fn ($item) => $this->extractCursorValues($item, $cursorFields), $sorted);

        $this->assertUniqueTuples($tuples);

        if (null === $boundary) {
            $items = \array_slice($sorted, 0, $limit + 1);
            $hasNext = \count($items) > $limit;
            if ($hasNext) {
                array_pop($items);
            }

            return new CursorSlice(
                $items,
                $hasNext && [] !== $items ? new CursorBoundary($this->extractCursorValues(end($items), $cursorFields)) : null,
                null,
                $hasNext,
            );
        }

        $values = $boundary->values;

        if (\count($values) !== \count($cursorFields)) {
            throw new InvalidArgumentException('Cursor values count does not match cursor fields count.');
        }

        if ($boundary->forward) {
            // Items strictly after the cursor values, in display order
            $start = \count($sorted);
            foreach ($tuples as $index => $tuple) {
                if ($this->isAfterTuple($tuple, $values, $direction)) {
                    $start = $index;
                    break;
                }
            }

            $items = \array_slice($sorted, $start, $limit + 1);
            $hasNext = \count($items) > $limit;
            if ($hasNext) {
                array_pop($items);
            }

            return new CursorSlice(
                $items,
                $hasNext && [] !== $items ? new CursorBoundary($this->extractCursorValues(end($items), $cursorFields)) : null,
                [] !== $items ? new CursorBoundary($this->extractCursorValues($items[0], $cursorFields), false) : null,
                $hasNext,
            );
        }

        // Backward: items strictly before the cursor values, keep the closest $limit
        $before = [];
        foreach ($tuples as $index => $tuple) {
            if ($this->isAfterTuple($tuple, $values, $direction) || 0 === $this->compareTuples($tuple, $values)) {
                break;
            }
            $before[] = $sorted[$index];
        }

        $hasPrevious = \count($before) > $limit;
        $items = $hasPrevious ? \array_slice($before, -$limit) : $before;

        return new CursorSlice(
            $items,
            [] !== $items ? new CursorBoundary($this->extractCursorValues(end($items), $cursorFields)) : null,
            $hasPrevious && [] !== $items ? new CursorBoundary($this->extractCursorValues($items[0], $cursorFields), false) : null,
            [] !== $items,
        );
    }

    /**
     * Whether an item tuple comes after the cursor values in display order.
     *
     * @param list<int|string|float> $tuple
     * @param list<int|string|float> $values
     */
    private function isAfterTuple(array $tuple, array $values, string $direction): bool
    {
        $comparison = $this->compareTuples($tuple, $values);

        return 'ASC' === $direction ? $comparison > 0 : $comparison < 0;
    }
}
