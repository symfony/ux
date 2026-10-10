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
use Symfony\UX\DesignTokens\Token\BorderToken;

#[CoversClass(BorderToken::class)]
final class BorderTokenTest extends TestCase
{
    public function testTypeIsBorder(): void
    {
        self::assertSame('border', new BorderToken(self::border(1, 'solid'))->getType());
    }

    /** @param string|array<string, mixed> $style */
    #[DataProvider('borders')]
    public function testToStringFormatsTheShorthand(int|float $width, string|array $style, string $expected): void
    {
        self::assertSame($expected, (string) new BorderToken(self::border($width, $style)));
    }

    /** @return iterable<string, array{int|float, string|array<string, mixed>, string}> */
    public static function borders(): iterable
    {
        yield 'thin solid' => [1, 'solid', '1px solid color(srgb 0 0 0)'];
        yield 'thick dashed' => [4, 'dashed', '4px dashed color(srgb 0 0 0)'];
        yield 'structured stroke' => [2, ['dashArray' => [['value' => 4, 'unit' => 'px']], 'lineCap' => 'round'], '2px dashed color(srgb 0 0 0)'];
    }

    /**
     * @param string|array<string, mixed> $style
     *
     * @return array<string, mixed>
     */
    private static function border(int|float $width, string|array $style): array
    {
        return ['width' => ['value' => $width, 'unit' => 'px'], 'style' => $style, 'color' => ['colorSpace' => 'srgb', 'components' => [0, 0, 0]]];
    }
}
