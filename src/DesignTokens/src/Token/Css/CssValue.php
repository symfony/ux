<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Token\Css;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class CssValue
{
    public static function stringify(mixed $value): string
    {
        if (\is_array($value)) {
            if (\array_key_exists('value', $value) && \array_key_exists('unit', $value)) {
                $numeric = $value['value'];
                $unit = $value['unit'];
                if ((\is_int($numeric) || \is_float($numeric)) && \is_string($unit)) {
                    return self::number($numeric).$unit;
                }
            }

            if (\array_key_exists('colorSpace', $value) && \array_key_exists('components', $value)) {
                return self::color($value);
            }

            return implode(', ', array_map(self::stringify(...), array_values($value)));
        }

        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (\is_int($value) || \is_float($value)) {
            return self::number($value);
        }

        return \is_string($value) ? $value : '';
    }

    /** PHP writes 0.00001 as "1.0E-5", which CSS rejects. */
    public static function number(int|float $number): string
    {
        $string = (string) $number;
        if (\is_int($number) || !preg_match('/^(-?)(\d+)(?:\.(\d+))?E([+-]\d+)$/', $string, $parts)) {
            return $string;
        }

        [, $sign, $integer, $fraction, $exponent] = $parts;
        $digits = $integer.$fraction;
        $point = \strlen($integer) + (int) $exponent;
        if ($point <= 0) {
            return $sign.'0.'.str_repeat('0', -$point).rtrim($digits, '0');
        }
        if ($point >= \strlen($digits)) {
            return $sign.$digits.str_repeat('0', $point - \strlen($digits));
        }

        return $sign.substr($digits, 0, $point).'.'.rtrim(substr($digits, $point), '0');
    }

    /** Control characters are escaped: a raw CR or FF would end the string. */
    public static function string(string $value): string
    {
        $escaped = preg_replace_callback(
            '/[\\\\"\x00-\x1F\x7F]/',
            static fn (array $match): string => match ($match[0]) {
                '\\', '"' => '\\'.$match[0],
                default => \sprintf('\\%X ', \ord($match[0])),
            },
            $value,
        );

        return '"'.$escaped.'"';
    }

    /** @param array<array-key, mixed> $value */
    private static function color(array $value): string
    {
        $space = $value['colorSpace'];
        $componentsValue = $value['components'];
        \assert(\is_string($space));
        \assert(\is_array($componentsValue));
        if (!array_is_list($componentsValue)) {
            return \sprintf('color(%s %s)', $space, self::stringify($componentsValue));
        }
        /** @var list<mixed> $componentsValue */
        $components = match ($space) {
            'hsl', 'hwb' => self::percentageComponents($componentsValue, [1, 2]),
            'lab', 'lch' => self::percentageComponents($componentsValue, [0]),
            'oklab', 'oklch' => self::okPercentageComponents($componentsValue),
            default => implode(' ', array_map(self::stringify(...), $componentsValue)),
        };
        $alpha = isset($value['alpha']) && 1 != $value['alpha'] ? ' / '.self::stringify($value['alpha']) : '';

        return match ($space) {
            'srgb', 'srgb-linear', 'display-p3', 'a98-rgb', 'prophoto-rgb', 'rec2020', 'xyz-d65', 'xyz-d50' => \sprintf('color(%s %s%s)', $space, $components, $alpha),
            'hsl' => \sprintf('hsl(%s%s)', $components, $alpha),
            'hwb' => \sprintf('hwb(%s%s)', $components, $alpha),
            default => \sprintf('%s(%s%s)', $space, $components, $alpha),
        };
    }

    /**
     * @param list<mixed> $components
     * @param list<int>   $percentIndexes
     */
    private static function percentageComponents(array $components, array $percentIndexes): string
    {
        return implode(' ', array_map(
            static fn (mixed $component, int $index): string => \in_array($index, $percentIndexes, true) && is_numeric($component)
                ? self::number(0 + $component).'%'
                : self::stringify($component),
            $components,
            array_keys($components),
        ));
    }

    /** @param list<mixed> $components */
    private static function okPercentageComponents(array $components): string
    {
        return implode(' ', array_map(
            static fn (mixed $component, int $index): string => 0 === $index && is_numeric($component)
                ? self::number(100 * $component).'%'
                : self::stringify($component),
            $components,
            array_keys($components),
        ));
    }
}
