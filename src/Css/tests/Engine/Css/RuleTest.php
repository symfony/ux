<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests\Engine\Css;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Css\Engine\Css\Rule;

final class RuleTest extends TestCase
{
    public static function provideSelectors(): iterable
    {
        yield 'one selector' => ['.c_red', ['.c_red']];
        yield 'nothing' => ['', []];
        yield 'only spaces' => ['   ', []];
        yield 'spaces around' => ['  .c_red  ', ['.c_red']];
        yield 'a list' => ['.a, .b ,.c', ['.a', '.b', '.c']];
        yield 'a trailing comma' => ['.a, ', ['.a']];
        yield 'a leading comma' => [', .a', ['.a']];
        yield 'a comma in parentheses' => [':is(:hover, [data-hover])', [':is(:hover, [data-hover])']];
        yield 'a list of parentheses' => [':is(:a, :b), :is(:c, :d)', [':is(:a, :b)', ':is(:c, :d)']];
        yield 'an unbalanced closing parenthesis' => ['.a), .b', ['.a)', '.b']];
        yield 'a comma in double quotes' => ['[title="a, b"]', ['[title="a, b"]']];
        yield 'a comma in single quotes' => ["[title='a, b']", ["[title='a, b']"]];
        yield 'an escaped comma' => ['.a\\,b, .c', ['.a\\,b', '.c']];
        yield 'an escaped quote' => ['[title="a\\",b"], .c', ['[title="a\\",b"]', '.c']];
        yield 'an escaped class of the engine' => ['.\\[\\&\\>svg\\]\\:size_4', ['.\\[\\&\\>svg\\]\\:size_4']];
        yield 'a multibyte selector' => ['.c_é, .c_日本', ['.c_é', '.c_日本']];
        yield 'a multibyte selector alone' => ['.c_日本', ['.c_日本']];
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('provideSelectors')]
    public function testSelectorsSplitOnCommasOutsideParenthesesAndQuotes(string $selector, array $expected): void
    {
        $rule = new Rule($selector);

        $this->assertSame($expected, $rule->selectors());
    }
}
