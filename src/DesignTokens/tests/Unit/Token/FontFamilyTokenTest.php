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
use Symfony\UX\DesignTokens\Token\FontFamilyToken;

#[CoversClass(FontFamilyToken::class)]
final class FontFamilyTokenTest extends TestCase
{
    public function testTypeIsFontFamily(): void
    {
        self::assertSame('fontFamily', new FontFamilyToken('sans-serif')->getType());
    }

    /** @param string|list<string> $value */
    #[DataProvider('fontFamilyProvider')]
    public function testToStringRendersCorrectly(string|array $value, string $expected): void
    {
        self::assertSame($expected, (string) new FontFamilyToken($value));
    }

    /** @return iterable<string, array{string|list<string>, string}> */
    public static function fontFamilyProvider(): iterable
    {
        yield 'generic keyword' => ['sans-serif', 'sans-serif'];
        yield 'identifier sequence' => ['Helvetica Neue', 'Helvetica Neue'];
        yield 'vendor identifier' => ['-apple-system', '-apple-system'];
        yield 'digit in a word' => ['Font Awesome 6 Free', '"Font Awesome 6 Free"'];
        yield 'leading digit' => ['1Password', '"1Password"'];
        yield 'css-wide keyword' => ['inherit', '"inherit"'];
        yield 'comma is part of the name' => ['Inter, sans-serif', '"Inter, sans-serif"'];
        yield 'single-item array' => [['Inter'], 'Inter'];
        yield 'list' => [['Inter', 'Roboto', 'sans-serif'], 'Inter, Roboto, sans-serif'];
        yield 'injection' => ['x; } body { display: none', '"x; } body { display: none"'];
        yield 'quote and backslash' => ['a"b\\c', '"a\\"b\\\\c"'];
        yield 'newline' => ["a\nb", '"a\\A b"'];
        yield 'carriage return' => ["a\r} body{background:red}", '"a\\D } body{background:red}"'];
        yield 'form feed' => ["a\fb", '"a\\C b"'];
        yield 'nul and delete' => ["a\0b\x7F", '"a\\0 b\\7F "'];
    }
}
