<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Fixtures;

final class TokenValues
{
    /** @return array{colorSpace: string, components: list<float>} */
    public static function color(float $red = 0.2, float $green = 0.4, float $blue = 0.8): array
    {
        return ['colorSpace' => 'srgb', 'components' => [$red, $green, $blue]];
    }

    /** @return array{value: int|float, unit: string} */
    public static function dimension(int|float $value, string $unit = 'px'): array
    {
        return ['value' => $value, 'unit' => $unit];
    }

    /** @return array<string, mixed> */
    public static function shadow(bool $inset = false): array
    {
        return [
            'color' => self::color(),
            'offsetX' => self::dimension(0),
            'offsetY' => self::dimension(2),
            'blur' => self::dimension(4),
            'spread' => self::dimension(0),
            'inset' => $inset,
        ];
    }

    /** @return array<string, mixed> */
    public static function border(): array
    {
        return ['width' => self::dimension(1), 'style' => 'solid', 'color' => self::color()];
    }

    /** @return array<string, mixed> */
    public static function transition(): array
    {
        return [
            'duration' => self::dimension(250, 'ms'),
            'timingFunction' => [0.4, 0, 0.2, 1],
            'delay' => self::dimension(0, 'ms'),
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    public static function typography(array $overrides = []): array
    {
        return [
            'fontFamily' => ['Inter', 'sans-serif'],
            'fontSize' => self::dimension(32),
            'fontWeight' => 700,
            'letterSpacing' => self::dimension(0),
            'lineHeight' => 1.2,
            ...$overrides,
        ];
    }
}
