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
use Symfony\UX\Css\Engine\Breakpoints;
use Symfony\UX\Css\Engine\Conditions;
use Symfony\UX\Css\Engine\Css\Declaration;
use Symfony\UX\Css\Engine\Css\Rule;
use Symfony\UX\Css\Engine\StyleDecoder;
use Symfony\UX\Css\Engine\StyleEncoder;
use Symfony\UX\Css\Engine\Stylesheet;
use Symfony\UX\Css\Engine\Tokens;
use Symfony\UX\Css\Engine\Utilities;
use Symfony\UX\Css\Exception\InvalidArgumentException;

final class StylesheetTest extends TestCase
{
    public static function providePandaCss(): iterable
    {
        yield 'nothing' => [[], ''];
        yield 'merged selectors' => [['_hover' => ['color' => 'red'], '_focus' => ['color' => 'red']], "@layer utilities {\n\n  .focus\\:c_red:is(:focus, [data-focus]),.hover\\:c_red:is(:hover, [data-hover]) {\n    color: red;\n}\n}"];
        yield 'merged media queries' => [['color' => ['base' => 'red', 'md' => 'red'], 'bg' => ['md' => 'red']], "@layer utilities {\n  .c_red {\n    color: red;\n}\n\n  @media screen and (min-width: 48rem) {\n    .md\\:bg_red {\n      background: red;\n}\n    .md\\:c_red {\n      color: red;\n}\n}\n}"];
        yield 'supports before media' => [['@supports (display: grid)' => ['color' => 'red'], 'bg' => 'blue', 'md' => ['color' => 'green']], "@layer utilities {\n  .bg_blue {\n    background: blue;\n}\n\n  @supports (display: grid) {\n    .\\[\\@supports_\\(display\\:_grid\\)\\]\\:c_red {\n      color: red;\n}\n}\n\n  @media screen and (min-width: 48rem) {\n    .md\\:c_green {\n      color: green;\n}\n}\n}"];
        yield 'conditions sorted by pseudo class' => [['color' => 'red', '_hover' => ['color' => 'blue', 'bg' => 'green']], "@layer utilities {\n  .c_red {\n    color: red;\n}\n\n  .hover\\:bg_green:is(:hover, [data-hover]) {\n    background: green;\n}\n\n  .hover\\:c_blue:is(:hover, [data-hover]) {\n    color: blue;\n}\n}"];
        yield 'nested at-rules' => [['@supports (display: grid)' => ['md' => ['color' => 'red']]], "@layer utilities {\n  @supports (display: grid) {\n    @media screen and (min-width: 48rem) {\n      .\\[\\@supports_\\(display\\:_grid\\)\\]\\:md\\:c_red {\n        color: red;\n}\n}\n}\n}"];
        yield 'numbers get px' => [['width' => 42, 'opacity' => 1, 'zIndex' => 0], "@layer utilities {\n  .op_1 {\n    opacity: 1;\n}\n\n  .z_0 {\n    z-index: 0;\n}\n\n  .w_42 {\n    width: 42px;\n}\n}"];
    }

    public function testBreakpointAtRulesBecomeMediaQueries(): void
    {
        $breakpoints = new Breakpoints(['sm' => '640px', 'md' => '768px']);
        $utilities = new Utilities(
            ['hideFrom' => ['className' => 'hide', 'transform' => ['__function' => 'transform']]],
            '_',
            null,
            ['hideFrom' => static fn (string $value): array => ['@breakpoint '.$value => ['display' => 'none']]],
        );
        $conditions = new Conditions([], $breakpoints);
        $entries = new StyleEncoder($utilities, $conditions)->encode(['hideFrom' => 'md']);
        $decoder = new StyleDecoder($utilities, $conditions);

        $css = new Stylesheet($breakpoints)->render($decoder->decode($entries));

        $this->assertSame("@layer utilities {\n  @media screen and (min-width: 48rem) {\n    .hide_md {\n      display: none;\n}\n}\n}", $css);
    }

    public function testTheTokensLayerComesBeforeTheUtilitiesLayer(): void
    {
        $utilities = new Utilities(['color' => ['className' => 'c']]);
        $conditions = new Conditions([], new Breakpoints([]));
        $tokens = [new Rule(':where(html)', [new Declaration('--colors-red', '#f00')])];
        $entries = new StyleEncoder($utilities, $conditions)->encode(['color' => 'red']);
        $decoder = new StyleDecoder($utilities, $conditions);
        $stylesheet = new Stylesheet();

        $css = $stylesheet->render($decoder->decode($entries), $tokens);
        $cssWithoutUtilities = $stylesheet->render([], $tokens);

        $this->assertSame("@layer tokens {\n  :where(html) {\n    --colors-red: #f00;\n}\n}\n\n@layer utilities {\n  .c_red {\n    color: red;\n}\n}", $css);
        $this->assertSame("@layer tokens {\n  :where(html) {\n    --colors-red: #f00;\n}\n}", $cssWithoutUtilities);
    }

    public function testTokensAreResolvedInAtRules(): void
    {
        $tokens = new Tokens(['sizes' => ['sm' => ['value' => '24rem']]]);
        $utilities = new Utilities(['color' => ['className' => 'c']], '_', $tokens);
        $conditions = new Conditions([], new Breakpoints([]));
        $styles = ['@media (min-width: token(sizes.sm))' => ['color' => 'red']];
        $entries = new StyleEncoder($utilities, $conditions)->encode($styles);
        $decoder = new StyleDecoder($utilities, $conditions);

        $css = new Stylesheet()->render($decoder->decode($entries));

        $this->assertSame("@layer utilities {\n  @media (min-width: 24rem) {\n    .\\[\\@media_\\(min-width\\:_token\\(sizes\\.sm\\)\\)\\]\\:c_red {\n      color: red;\n}\n}\n}", $css);
    }

    public function testUnknownBreakpointAtRuleIsRejected(): void
    {
        $breakpoints = new Breakpoints(['sm' => '640px']);
        $utilities = new Utilities(
            ['hideFrom' => ['className' => 'hide', 'transform' => ['__function' => 'transform']]],
            '_',
            null,
            ['hideFrom' => static fn (string $value): array => ['@breakpoint '.$value => ['display' => 'none']]],
        );
        $conditions = new Conditions([], $breakpoints);
        $entries = new StyleEncoder($utilities, $conditions)->encode(['hideFrom' => 'xl']);
        $decoder = new StyleDecoder($utilities, $conditions);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No "xl" breakpoint found.');

        new Stylesheet($breakpoints)->render($decoder->decode($entries));
    }

    #[DataProvider('providePandaCss')]
    public function testRenderMatchesPanda(array $styles, string $expected): void
    {
        $utilities = new Utilities([
            'color' => ['className' => 'c'],
            'background' => ['className' => 'bg', 'shorthand' => 'bg'],
            'width' => ['className' => 'w', 'shorthand' => 'w'],
            'opacity' => ['className' => 'op'],
            'zIndex' => ['className' => 'z'],
        ]);
        $conditions = new Conditions(
            ['hover' => '&:is(:hover, [data-hover])', 'focus' => '&:is(:focus, [data-focus])'],
            new Breakpoints(['sm' => '640px', 'md' => '768px', 'lg' => '1024px']),
        );
        $entries = new StyleEncoder($utilities, $conditions)->encode($styles);
        $decoder = new StyleDecoder($utilities, $conditions);

        $css = new Stylesheet()->render($decoder->decode($entries));

        $this->assertSame($expected, $css);
    }
}
