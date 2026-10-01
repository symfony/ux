<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests\Fixtures;

final class Dtcg
{
    public static function color(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (3 === \strlen($hex)) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $components = array_map(
            static fn (string $pair): float => round(hexdec($pair) / 255, 4),
            str_split($hex, 2),
        );

        return [
            '$type' => 'color',
            '$value' => ['colorSpace' => 'srgb', 'components' => $components, 'hex' => '#'.$hex],
        ];
    }

    public static function dimension(int|float $value, string $unit = 'rem'): array
    {
        return ['$type' => 'dimension', '$value' => ['value' => $value, 'unit' => $unit]];
    }

    public static function number(int|float $value): array
    {
        return ['$type' => 'number', '$value' => $value];
    }

    public static function alias(string $path): array
    {
        return ['$value' => '{'.$path.'}'];
    }
}
