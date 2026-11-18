<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Bench;

final class MarkdownTables
{
    private const MINIMUM_WIDTH = 3;

    public static function align(string $markdown): string
    {
        $aligned = [];
        $table = [];

        foreach (explode("\n", $markdown) as $line) {
            if (str_starts_with($line, '|')) {
                $table[] = $line;

                continue;
            }

            if ([] !== $table) {
                array_push($aligned, ...self::alignTable($table));
                $table = [];
            }

            $aligned[] = $line;
        }

        if ([] !== $table) {
            array_push($aligned, ...self::alignTable($table));
        }

        return implode("\n", $aligned);
    }

    /**
     * @param list<string> $lines
     *
     * @return list<string>
     */
    private static function alignTable(array $lines): array
    {
        $rows = array_map(self::cells(...), $lines);
        $widths = self::widths($rows);

        $aligned = [];
        foreach ($rows as $row) {
            $aligned[] = self::isSeparator($row) ? self::separator($widths) : self::row($row, $widths);
        }

        return $aligned;
    }

    /**
     * @return list<string>
     */
    private static function cells(string $line): array
    {
        $inner = substr(rtrim($line), 1);
        if (str_ends_with($inner, '|')) {
            $inner = substr($inner, 0, -1);
        }

        return array_map(trim(...), explode('|', $inner));
    }

    /**
     * @param list<list<string>> $rows
     *
     * @return list<int>
     */
    private static function widths(array $rows): array
    {
        $widths = [];
        foreach ($rows as $row) {
            if (self::isSeparator($row)) {
                continue;
            }

            foreach ($row as $index => $cell) {
                $widths[$index] = max($widths[$index] ?? self::MINIMUM_WIDTH, mb_strlen($cell));
            }
        }

        return $widths;
    }

    /**
     * @param list<string> $row
     */
    private static function isSeparator(array $row): bool
    {
        foreach ($row as $cell) {
            if (1 !== preg_match('/^:?-+:?$/', $cell)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param list<int> $widths
     */
    private static function separator(array $widths): string
    {
        $cells = array_map(static fn (int $width): string => str_repeat('-', $width), $widths);

        return \sprintf('| %s |', implode(' | ', $cells));
    }

    /**
     * @param list<string> $row
     * @param list<int>    $widths
     */
    private static function row(array $row, array $widths): string
    {
        $cells = [];
        foreach ($widths as $index => $width) {
            $cells[] = $row[$index] ?? '';
            $cells[$index] .= str_repeat(' ', max(0, $width - mb_strlen($cells[$index])));
        }

        return \sprintf('| %s |', implode(' | ', $cells));
    }
}
