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

use Symfony\UX\DataTable\PropertyReader;

/**
 * Sorts and filters rows (arrays or objects) in memory.
 *
 * @author Simon Daigre <simon@daig.re>
 */
final class ArrayQuery implements QueryInterface
{
    /**
     * @var list<mixed>
     */
    private array $rows;

    /**
     * @var list<array{string, \SortDirection}>
     */
    private array $sort = [];

    /**
     * @param iterable<mixed> $rows
     */
    public function __construct(
        iterable $rows,
        private readonly PropertyReader $reader = new PropertyReader(),
    ) {
        $this->rows = \is_array($rows) ? array_values($rows) : iterator_to_array($rows, false);
    }

    public function sort(string $path, \SortDirection $direction): void
    {
        $this->sort[] = [$path, $direction];
    }

    public function filter(string $path, Operator $operator, mixed $value): void
    {
        $this->rows = array_values(array_filter($this->rows, fn (mixed $row): bool => self::matches($this->reader->read($row, $path), $operator, $value)));
    }

    public function search(array $paths, string $term): void
    {
        $this->rows = array_values(array_filter($this->rows, function (mixed $row) use ($paths, $term): bool {
            foreach ($paths as $path) {
                if (self::matches($this->reader->read($row, $path), Operator::Contains, $term)) {
                    return true;
                }
            }

            return false;
        }));
    }

    /**
     * @return list<mixed>
     */
    public function getSource(): array
    {
        $rows = $this->rows;
        usort($rows, function (mixed $a, mixed $b): int {
            foreach ($this->sort as [$path, $direction]) {
                if (0 !== $result = self::sortKey($this->reader->read($a, $path)) <=> self::sortKey($this->reader->read($b, $path))) {
                    return \SortDirection::Ascending === $direction ? $result : -$result;
                }
            }

            return 0;
        });

        return $rows;
    }

    private static function matches(mixed $actual, Operator $operator, mixed $expected): bool
    {
        // Like SQL, a null value matches no condition
        if (null === $actual) {
            return false;
        }

        // Like Doctrine, a backed enum is compared by its value
        $actual = self::scalar($actual);
        $expected = Operator::In === $operator ? array_map(self::scalar(...), is_iterable($expected) ? iterator_to_array($expected, false) : [$expected]) : self::scalar($expected);

        return match ($operator) {
            Operator::Equals => $actual == $expected,
            Operator::NotEquals => $actual != $expected,
            Operator::Contains => str_contains(self::lower($actual), self::lower($expected)),
            Operator::StartsWith => str_starts_with(self::lower($actual), self::lower($expected)),
            Operator::EndsWith => str_ends_with(self::lower($actual), self::lower($expected)),
            Operator::GreaterThan => $actual > $expected,
            Operator::GreaterThanOrEqual => $actual >= $expected,
            Operator::LessThan => $actual < $expected,
            Operator::LessThanOrEqual => $actual <= $expected,
            Operator::In => \is_array($expected) && \in_array($actual, $expected),
        };
    }

    private static function scalar(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }

    /**
     * Sorts enums by value or name, and strings case-insensitively.
     */
    private static function sortKey(mixed $value): mixed
    {
        $value = match (true) {
            $value instanceof \BackedEnum => $value->value,
            $value instanceof \UnitEnum => $value->name,
            default => $value,
        };

        return \is_string($value) ? mb_strtolower($value) : $value;
    }

    private static function lower(mixed $value): string
    {
        return mb_strtolower(\is_scalar($value) || $value instanceof \Stringable ? (string) $value : '');
    }
}
