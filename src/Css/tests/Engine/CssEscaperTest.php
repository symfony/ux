<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests\Engine;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Css\Engine\CssEscaper;
use Symfony\UX\Css\Exception\InvalidArgumentException;

final class CssEscaperTest extends TestCase
{
    public static function providePandaClassNames(): iterable
    {
        yield 'simple' => ['a0b', 'a0b'];
        yield 'simple with underscore' => ['bg_red', 'bg_red'];
        yield 'leading digit' => ['0a', '\30a'];
        yield 'leading dash and digit' => ['-0a', '-\30a'];
        yield 'breakpoint prefix' => ['2xl:bg_red', '\32xl\:bg_red'];
        yield 'decimal' => ['m_0.5', 'm_0\.5'];
        yield 'important' => ['m_0.5!', 'm_0\.5\!'];
        yield 'invalid characters' => ['w:_$-1/2', 'w\:_\$-1\/2'];
        yield 'css variable' => ['--a', '\--a'];
        yield 'non ASCII kept' => ["\u{80}\x2D\x5F\u{A9}", "\u{80}-_\u{A9}"];
        yield 'space and bang' => ["\x20\x21\x78\x79", '\ \!xy'];
        yield 'control characters' => ["\x01\x02\x1E\x1F", '\1\2\1e\1f'];
        yield 'brackets' => ['decoration-[#ccc]', 'decoration-\[\#ccc\]'];
        yield 'arbitrary at-rule' => ['[@media]:bg_red', '\[\@media\]\:bg_red'];
        yield 'slash' => ['bg-red-500/50', 'bg-red-500\/50'];
        yield 'arbitrary value' => ['p-[8px_4px]', 'p-\[8px_4px\]'];
        yield 'fraction' => ['w_1/3', 'w_1\/3'];
        yield 'url' => ["hover:bg-[url('https://github.com/img.png')]", "hover\\:bg-\\[url\\(\\'https\\:\\/\\/github\\.com\\/img\\.png\\'\\)\\]"];
        yield 'leading non ASCII digit' => ["\u{0663}xl:bg_red", "\u{0663}xl\\:bg_red"];
        yield 'leading fullwidth digit' => ["\u{FF11}a", "\u{FF11}a"];
        yield 'dash then non ASCII digit' => ["-\u{0663}a", "\\-\u{0663}a"];
    }

    #[DataProvider('providePandaClassNames')]
    public function testEscapeMatchesPanda(string $className, string $expected): void
    {
        $this->assertSame($expected, CssEscaper::escape($className));
    }

    public function testCharactersOutsideTheBasicMultilingualPlaneAreKept(): void
    {
        $this->assertSame('c_👋', CssEscaper::escape('c_👋'));
    }

    public function testInvalidUtf8IsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CssEscaper::escape("c_\xFF");
    }
}
