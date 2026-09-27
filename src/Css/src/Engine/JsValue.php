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

use Symfony\UX\Css\Exception\InvalidArgumentException;
use Symfony\UX\Css\Exception\UnsupportedStyleException;

/**
 * JavaScript's string conversions, so that values come out exactly as in Panda.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class JsValue
{
    // JavaScript's \s; PCRE's \s differs on U+0085 and U+FEFF
    public const WHITESPACE = '[\t\n\x{0B}\f\r \x{A0}\x{1680}\x{2000}-\x{200A}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}\x{FEFF}]';

    public static function toString(string|int|float|bool $value): string
    {
        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (!\is_float($value)) {
            return (string) $value;
        }
        if (!is_finite($value)) {
            throw new UnsupportedStyleException(\sprintf('The number "%s" cannot be used as a style value.', $value));
        }

        $number = json_encode($value, \JSON_THROW_ON_ERROR);
        if (str_contains($number, 'e')) {
            $message = \sprintf('The number "%s" is written with an exponent, which is not supported.', $number);

            throw new UnsupportedStyleException($message);
        }

        return '-0' === $number ? '0' : $number;
    }

    /**
     * Port of `parseValue()` (packages/core/src/style-decoder.ts): numeric strings become numbers, "true" and "false" booleans.
     */
    public static function parse(string $value): string|int|float|bool
    {
        if (preg_match('/^0\d+$/', $value)) {
            return $value;
        }

        $number = self::toNumber($value);
        if (null !== $number) {
            return $number;
        }

        return match ($value) {
            'true' => true,
            'false' => false,
            default => $value,
        };
    }

    /**
     * The keys of an object in the order JavaScript enumerates them: integer-like keys first, ascending, then the others as inserted.
     *
     * @param list<string|int> $keys
     *
     * @return list<string>
     */
    public static function objectKeys(array $keys): array
    {
        $indexes = [];
        $others = [];
        foreach ($keys as $key) {
            $key = (string) $key;
            if (preg_match('/^(0|[1-9][0-9]*)$/', $key) && (int) $key < 4294967295) {
                $indexes[] = $key;
            } else {
                $others[] = $key;
            }
        }
        sort($indexes, \SORT_NUMERIC);

        return [...$indexes, ...$others];
    }

    public static function isTruthy(mixed $value): bool
    {
        return !\in_array($value, [null, false, '', 0, 0.0], true) && !(\is_float($value) && is_nan($value));
    }

    public static function collapseWhitespace(string $value): string
    {
        return preg_replace('/'.self::WHITESPACE.'+/u', ' ', $value) ?? throw self::invalidUtf8();
    }

    public static function trim(string $value): string
    {
        $pattern = '/^'.self::WHITESPACE.'+|'.self::WHITESPACE.'+$/u';

        return preg_replace($pattern, '', $value) ?? throw self::invalidUtf8();
    }

    /**
     * JavaScript's `Number()` on a string, or null for NaN; infinities stay strings.
     */
    public static function toNumber(string $value): int|float|null
    {
        $value = self::trim($value);
        if ('' === $value) {
            return 0;
        }
        if (preg_match('/^0[xX]([0-9a-fA-F]+)$/', $value, $matches)) {
            return hexdec($matches[1]);
        }
        if (preg_match('/^0[oO]([0-7]+)$/', $value, $matches)) {
            return octdec($matches[1]);
        }
        if (preg_match('/^0[bB]([01]+)$/', $value, $matches)) {
            return bindec($matches[1]);
        }
        if (!preg_match('/^[+-]?(\d+\.?\d*|\.\d+)([eE][+-]?\d+)?$/', $value)) {
            return null;
        }

        $number = (float) $value;
        if (!is_finite($number)) {
            return null;
        }

        if (
            floor($number) === $number
            && abs($number) < 2 ** 53
            && !(0.0 === $number && str_starts_with($value, '-'))
        ) {
            return (int) $number;
        }

        return $number;
    }

    private static function invalidUtf8(): InvalidArgumentException
    {
        return new InvalidArgumentException('Style values must be valid UTF-8.');
    }
}
