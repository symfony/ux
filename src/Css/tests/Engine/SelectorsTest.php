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
use Symfony\UX\Css\Engine\Selectors;

final class SelectorsTest extends TestCase
{
    /**
     * @param array{string, string}|array{null, string} $expected
     */
    #[DataProvider('provideTrailingPseudos')]
    public function testExtractTrailingPseudos(string $selector, array $expected): void
    {
        $this->assertSame($expected, Selectors::extractTrailingPseudos($selector));
    }

    public static function provideTrailingPseudos(): iterable
    {
        yield 'a pseudo-element alone' => ['::selection', ['::selection', '']];
        yield 'after a compound selector' => ['a::before', ['::before', 'a']];
        yield 'after a combinator' => ['.card ::after', ['::after', '.card ']];
        yield 'with an argument' => ['p::part(label)', ['::part(label)', 'p']];
        yield 'several in a row' => ['a::before::after', ['::before::after', 'a']];
        yield 'shared by a list' => ['a::before, b::before', ['::before', 'a, b']];
        yield 'different in a list' => ['::before, ::after', [null, '::before, ::after']];
        yield 'missing from a part of the list' => ['a, b::before', [null, 'a, b::before']];
        yield 'a pseudo-class' => [':-moz-focusring', [null, ':-moz-focusring']];
        yield 'no pseudo-element' => ['input:where([type=button])', [null, 'input:where([type=button])']];
    }
}
