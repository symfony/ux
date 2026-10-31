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
 * Port of Panda's unit conversions (packages/shared/src/unit-conversion.ts).
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class Units
{
    private const BASE_FONT_SIZE = 16;

    public static function toPx(string $value): string
    {
        return match (self::unit($value)) {
            null, 'px' => $value,
            default => JsValue::toString(self::parseFloat($value) * self::BASE_FONT_SIZE).'px',
        };
    }

    public static function toRem(string $value): string
    {
        return match (self::unit($value)) {
            null, 'rem' => $value,
            'em' => JsValue::toString(self::parseFloat($value)).'rem',
            default => JsValue::toString(self::parseFloat($value) / self::BASE_FONT_SIZE).'rem',
        };
    }

    public static function parseFloat(string $value): float
    {
        if (!preg_match('/^\s*([+-]?(?:\d+\.?\d*|\.\d+)(?:[eE][+-]?\d+)?)/', $value, $matches)) {
            return \NAN;
        }

        return (float) $matches[1];
    }

    private static function unit(string $value): ?string
    {
        return preg_match('/-?\d+(?:\.\d+|\d*)(px|em|rem)/', $value, $matches) ? $matches[1] : null;
    }
}
