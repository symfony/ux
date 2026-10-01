<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Unit\Token;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\DesignTokens\Token\ColorToken;

#[CoversClass(ColorToken::class)]
final class ColorTokenTest extends TestCase
{
    public function testTypeIsColor(): void
    {
        self::assertSame('color', new ColorToken(['colorSpace' => 'srgb', 'components' => [0.2, 0.4, 0.8]])->getType());
    }

    /** @param array<string, mixed> $value */
    #[DataProvider('colors')]
    public function testToStringIsACssColor(array $value, string $expected): void
    {
        self::assertSame($expected, (string) new ColorToken($value));
    }

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function colors(): iterable
    {
        yield 'srgb' => [['colorSpace' => 'srgb', 'components' => [0.2, 0.4, 0.8]], 'color(srgb 0.2 0.4 0.8)'];
        yield 'srgb with alpha' => [['colorSpace' => 'srgb', 'components' => [0.2, 0.4, 0.8], 'alpha' => 0.5], 'color(srgb 0.2 0.4 0.8 / 0.5)'];
        yield 'oklch' => [['colorSpace' => 'oklch', 'components' => [0.7, 0.15, 210]], 'oklch(70% 0.15 210)'];
    }

    public function testImplementsStringable(): void
    {
        self::assertInstanceOf(\Stringable::class, new ColorToken(['colorSpace' => 'oklch', 'components' => [0.7, 0.15, 210]]));
    }
}
