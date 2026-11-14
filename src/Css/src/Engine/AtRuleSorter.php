<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Engine;

/**
 * Port of Panda's `sortAtRules()` (packages/core/src/sort-at-rules.ts), a copy of sort-css-media-queries.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class AtRuleSorter
{
    private const MAX_VALUE = \PHP_FLOAT_MAX;

    private const CACHED_QUERIES = 1000;

    /**
     * @var array<string, array{print: bool, printOnly: bool, min: bool, max: bool, length: float}>
     */
    private static array $attributes = [];

    public static function compare(string $a, string $b): int
    {
        $first = self::attributes($a);
        $second = self::attributes($b);

        if ($first['print'] && $second['print']) {
            if (!$first['printOnly'] && $second['printOnly']) {
                return 1;
            }
            if ($first['printOnly'] && !$second['printOnly']) {
                return -1;
            }

            return strcmp($a, $b) <=> 0;
        }
        if ($first['print']) {
            return 1;
        }
        if ($second['print']) {
            return -1;
        }

        $minA = $first['min'];
        $maxA = $first['max'];
        $minB = $second['min'];
        $maxB = $second['max'];

        if ($minA && $maxB) {
            return -1;
        }
        if ($maxA && $minB) {
            return 1;
        }

        $lengthA = $first['length'];
        $lengthB = $second['length'];
        if (self::MAX_VALUE === $lengthA && self::MAX_VALUE === $lengthB) {
            return strcmp($a, $b) <=> 0;
        }
        if (self::MAX_VALUE === $lengthA) {
            return 1;
        }
        if (self::MAX_VALUE === $lengthB) {
            return -1;
        }
        if ($lengthA > $lengthB) {
            return $maxA ? -1 : 1;
        }
        if ($lengthA < $lengthB) {
            return $maxA ? 1 : -1;
        }

        return strcmp($a, $b) <=> 0;
    }

    /**
     * @return array{print: bool, printOnly: bool, min: bool, max: bool, length: float}
     */
    private static function attributes(string $query): array
    {
        if (isset(self::$attributes[$query])) {
            return self::$attributes[$query];
        }
        if (\count(self::$attributes) >= self::CACHED_QUERIES) {
            self::$attributes = [];
        }

        return self::$attributes[$query] = [
            'print' => 1 === preg_match('/print/i', $query),
            'printOnly' => 1 === preg_match('/^print$/i', $query),
            'min' => self::isMinWidth($query) || self::isMinHeight($query),
            'max' => self::isMaxWidth($query) || self::isMaxHeight($query),
            'length' => self::queryLength($query),
        ];
    }

    private static function queryLength(string $query): float
    {
        $matched = preg_match('/(-?\d*\.?\d+)(ch|em|ex|px|rem)/', $query, $length);
        if (!$matched && (self::isMinWidth($query) || self::isMinHeight($query))) {
            $matched = preg_match('/(\d)/', $query, $length);
            $length[2] = null;
        }
        if (!$matched) {
            return self::MAX_VALUE;
        }

        $number = (float) $length[1];

        return match ($length[2]) {
            'ch' => $number * 8.8984375,
            'em', 'rem' => $number * 16,
            'ex' => $number * 8.296875,
            default => $number,
        };
    }

    private static function isMinWidth(string $query): bool
    {
        return self::testQuery(
            $query,
            '/(!?\(\s*min(-device-)?-width)(.|\n)+\(\s*max(-device)?-width/i',
            '/(!?\(\s*max(-device)?-width)(.|\n)+\(\s*min(-device)?-width/i',
            '/\(\s*min(-device)?-width/i',
        );
    }

    private static function isMaxWidth(string $query): bool
    {
        return self::testQuery(
            $query,
            '/(!?\(\s*max(-device)?-width)(.|\n)+\(\s*min(-device)?-width/i',
            '/(!?\(\s*min(-device-)?-width)(.|\n)+\(\s*max(-device)?-width/i',
            '/\(\s*max(-device)?-width/i',
        );
    }

    private static function isMinHeight(string $query): bool
    {
        return self::testQuery(
            $query,
            '/(!?\(\s*min(-device)?-height)(.|\n)+\(\s*max(-device)?-height/i',
            '/(!?\(\s*max(-device)?-height)(.|\n)+\(\s*min(-device)?-height/i',
            '/\(\s*min(-device)?-height/i',
        );
    }

    private static function isMaxHeight(string $query): bool
    {
        return self::testQuery(
            $query,
            '/(!?\(\s*max(-device)?-height)(.|\n)+\(\s*min(-device)?-height/i',
            '/(!?\(\s*min(-device)?-height)(.|\n)+\(\s*max(-device)?-height/i',
            '/\(\s*max(-device)?-height/i',
        );
    }

    private static function testQuery(
        string $query,
        string $doubleTestTrue,
        string $doubleTestFalse,
        string $singleTest,
    ): bool {
        if (preg_match($doubleTestTrue, $query)) {
            return true;
        }
        if (preg_match($doubleTestFalse, $query)) {
            return false;
        }

        return 1 === preg_match($singleTest, $query);
    }
}
