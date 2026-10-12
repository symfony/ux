<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Unit\Token\Css;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\DesignTokens\Token\Css\CssValue;

#[CoversClass(CssValue::class)]
final class CssValueTest extends TestCase
{
    public function testStringifyHandlesPrimitiveAndStructuredValues(): void
    {
        self::assertSame('16px', CssValue::stringify(['value' => 16, 'unit' => 'px']));
        self::assertSame('true', CssValue::stringify(true));
        self::assertSame('42', CssValue::stringify(42));
        self::assertSame('Inter, sans-serif', CssValue::stringify(['Inter', 'sans-serif']));
        self::assertSame('', CssValue::stringify(null));
    }

    public function testAnOpaqueColorOmitsItsAlpha(): void
    {
        self::assertSame('color(srgb 0.1 0.3 0.8)', CssValue::stringify(['colorSpace' => 'srgb', 'components' => [0.1, 0.3, 0.8], 'alpha' => 1]));
        self::assertSame('oklch(62% 0.18 255)', CssValue::stringify(['colorSpace' => 'oklch', 'components' => [0.62, 0.18, 255], 'alpha' => 1.0]));
        self::assertSame('hsl(120 50% 25% / 0.99)', CssValue::stringify(['colorSpace' => 'hsl', 'components' => [120, 50, 25], 'alpha' => 0.99]));
    }

    public function testStringifyHandlesColorSpacesAndAlpha(): void
    {
        self::assertSame('color(srgb 0.2 0.4 0.8 / 0.5)', CssValue::stringify([
            'colorSpace' => 'srgb',
            'components' => [0.2, 0.4, 0.8],
            'alpha' => 0.5,
        ]));
        self::assertSame('hsl(120 50% 25%)', CssValue::stringify([
            'colorSpace' => 'hsl',
            'components' => [120, 50, 25],
        ]));
        self::assertSame('hwb(120 50% 25% / 0.25)', CssValue::stringify([
            'colorSpace' => 'hwb',
            'components' => [120, 50, 25],
            'alpha' => 0.25,
        ]));
        self::assertSame('lab(50% 20 30)', CssValue::stringify([
            'colorSpace' => 'lab',
            'components' => [50, 20, 30],
        ]));
        self::assertSame('oklch(72% 0.11 221.19)', CssValue::stringify([
            'colorSpace' => 'oklch',
            'components' => [0.72, 0.11, 221.19],
        ]));
        self::assertSame('oklch(0.001% 0.00002 0)', CssValue::stringify([
            'colorSpace' => 'oklch',
            'components' => [0.00001, 0.00002, 0],
        ]));
        self::assertSame('color(srgb 0.000001 0 1)', CssValue::stringify([
            'colorSpace' => 'srgb',
            'components' => [0.000001, 0, 1],
        ]));
    }
}
