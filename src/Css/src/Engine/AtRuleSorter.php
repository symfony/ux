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

    public static function compare(string $a, string $b): int
    {
        if (null !== $print = self::comparePrint($a, $b)) {
            return $print;
        }

        $minA = self::isMinWidth($a) || self::isMinHeight($a);
        $maxA = self::isMaxWidth($a) || self::isMaxHeight($a);
        $minB = self::isMinWidth($b) || self::isMinHeight($b);
        $maxB = self::isMaxWidth($b) || self::isMaxHeight($b);

        if ($minA && $maxB) {
            return -1;
        }
        if ($maxA && $minB) {
            return 1;
        }

        $lengthA = self::queryLength($a);
        $lengthB = self::queryLength($b);
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

    private static function comparePrint(string $a, string $b): ?int
    {
        $isPrintA = 1 === preg_match('/print/i', $a);
        $isPrintOnlyA = 1 === preg_match('/^print$/i', $a);
        $isPrintB = 1 === preg_match('/print/i', $b);
        $isPrintOnlyB = 1 === preg_match('/^print$/i', $b);

        if ($isPrintA && $isPrintB) {
            if (!$isPrintOnlyA && $isPrintOnlyB) {
                return 1;
            }
            if ($isPrintOnlyA && !$isPrintOnlyB) {
                return -1;
            }

            return strcmp($a, $b) <=> 0;
        }
        if ($isPrintA) {
            return 1;
        }
        if ($isPrintB) {
            return -1;
        }

        return null;
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
