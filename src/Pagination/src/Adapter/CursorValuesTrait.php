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

use Symfony\UX\Pagination\Exception\InvalidArgumentException;
use Symfony\UX\Pagination\Exception\RuntimeException;

/**
 * Shared cursor value extraction and comparison logic.
 *
 * A cursor is an opaque token wrapping the boundary values of a page and
 * the direction it points to: "next" cursors hold the values of the last
 * item of a page, "prev" cursors hold the values of its first item.
 *
 * @internal
 *
 * @author Simon André <smn.andre@gmail.com>
 */
trait CursorValuesTrait
{
    /**
     * @param list<string> $fields
     *
     * @return list<int|string|float>
     */
    private function extractCursorValues(mixed $item, array $fields): array
    {
        $values = [];
        foreach ($fields as $field) {
            $values[] = $this->extractSingleCursorValue($item, $field);
        }

        return $values;
    }

    private function extractSingleCursorValue(mixed $item, string $field): int|string|float
    {
        if (\is_object($item)) {
            $getter = 'get'.ucfirst($field);
            if (method_exists($item, $getter)) {
                return $this->normalizeCursorValue($item->$getter());
            }
            // Try is* prefix for booleans
            $isGetter = 'is'.ucfirst($field);
            if (method_exists($item, $isGetter)) {
                return $this->normalizeCursorValue($item->$isGetter());
            }
            $publicProperties = get_object_vars($item);
            if (\array_key_exists($field, $publicProperties)) {
                return $this->normalizeCursorValue($publicProperties[$field]);
            }
        }

        if (\is_array($item) && \array_key_exists($field, $item)) {
            return $this->normalizeCursorValue($item[$field]);
        }

        if (\is_scalar($item) && 'id' === $field) {
            return $this->normalizeCursorValue($item);
        }

        throw new RuntimeException(\sprintf('Cannot extract cursor field "%s" from item of type "%s".', $field, get_debug_type($item)));
    }

    private function normalizeCursorValue(mixed $value): int|string|float
    {
        if (null === $value) {
            throw new RuntimeException('Cursor values must be non-null.');
        }

        if (\is_bool($value)) {
            return $value ? 1 : 0;
        }

        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value)
                ->setTimezone(new \DateTimeZone('UTC'))
                ->format('Y-m-d\TH:i:s.u\Z');
        }

        if (\is_int($value) || \is_string($value) || \is_float($value)) {
            return $value;
        }

        throw new RuntimeException(\sprintf('Cursor values must be scalar or \DateTimeInterface, got "%s".', get_debug_type($value)));
    }

    /**
     * @param list<list<int|string|float>> $tuples
     */
    private function assertUniqueTuples(array $tuples): void
    {
        $seen = [];
        foreach ($tuples as $tuple) {
            $key = json_encode($tuple, \JSON_THROW_ON_ERROR);
            if (isset($seen[$key])) {
                throw new InvalidArgumentException('Cursor order is not total: duplicate cursor tuples were found. Add a unique tie-breaker field.');
            }
            $seen[$key] = true;
        }
    }

    /**
     * Lexicographic tuple comparison of normalized cursor values.
     *
     * @param array<int|string|float> $a
     * @param array<int|string|float> $b
     */
    private function compareTuples(array $a, array $b): int
    {
        foreach ($a as $i => $value) {
            $comparison = $value <=> ($b[$i] ?? null);
            if (0 !== $comparison) {
                return $comparison;
            }
        }

        return 0;
    }
}
