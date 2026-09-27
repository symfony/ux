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
use Symfony\UX\Css\Engine\Tokens;
use Symfony\UX\Css\Exception\InvalidArgumentException;
use Symfony\UX\Css\Exception\UnsupportedStyleException;

final class TokensTest extends TestCase
{
    public static function provideVars(): iterable
    {
        yield 'nested token' => ['colors.red.300', 'var(--pd-colors-red-300)'];
        yield 'DEFAULT is dropped from the name' => ['colors.red', 'var(--pd-colors-red)'];
        yield 'camel case becomes dash case' => ['colors.fooBar', 'var(--pd-colors-foo-bar)'];
        yield 'dots are escaped' => ['spacing.1.5', 'var(--pd-spacing-1\.5)'];
        yield 'semantic token' => ['colors.primary', 'var(--pd-colors-primary)'];
        yield 'negative spacing' => ['spacing.-4', 'calc(var(--pd-spacing-4) * -1)'];
        yield 'negative semantic spacing' => ['spacing.-gutter', 'calc(var(--pd-spacing-gutter) * -1)'];
        yield 'breakpoint' => ['breakpoints.md', 'var(--pd-breakpoints-md)'];
        yield 'breakpoint size' => ['sizes.breakpoint-sm', 'var(--pd-sizes-breakpoint-sm)'];
        yield 'virtual color palette' => ['colors.colorPalette.300', 'var(--pd-colors-color-palette-300)'];
        yield 'no negative zero' => ['spacing.-0', null];
        yield 'empty token' => ['empty.gone', null];
        yield 'unknown token' => ['colors.nope', null];
    }

    #[DataProvider('provideVars')]
    public function testGetVar(string $name, ?string $expected): void
    {
        $tokens = self::createTokens();

        $this->assertSame($expected, $tokens->getVar($name));
    }

    public function testGetCategoryValues(): void
    {
        $tokens = self::createTokens();

        $this->assertSame([
            '0' => 'var(--pd-spacing-0)',
            '4' => 'var(--pd-spacing-4)',
            '1.5' => 'var(--pd-spacing-1\.5)',
            'px' => 'var(--pd-spacing-px)',
            'gutter' => 'var(--pd-spacing-gutter)',
            '-4' => 'calc(var(--pd-spacing-4) * -1)',
            '-1.5' => 'calc(var(--pd-spacing-1\.5) * -1)',
            '-px' => 'calc(var(--pd-spacing-px) * -1)',
            '-gutter' => 'calc(var(--pd-spacing-gutter) * -1)',
        ], $tokens->getCategoryValues('spacing'));
        $this->assertSame([
            'red.300' => 'var(--pd-colors-red-300)',
            'red' => 'var(--pd-colors-red)',
            'fooBar' => 'var(--pd-colors-foo-bar)',
            'ghost' => 'var(--pd-colors-ghost)',
            'alias' => 'var(--pd-colors-alias)',
            'primary' => 'var(--pd-colors-primary)',
            'text' => 'var(--pd-colors-text)',
            'mixed' => 'var(--pd-colors-mixed)',
            'colorPalette.300' => 'var(--pd-colors-color-palette-300)',
            'colorPalette' => 'var(--pd-colors-color-palette)',
        ], $tokens->getCategoryValues('colors'));
        $this->assertNull($tokens->getCategoryValues('nope'));
    }

    public static function provideValues(): iterable
    {
        yield 'plain value' => ['colors.red.300', '#f00'];
        yield 'number' => ['opacity.half', 0.5];
        yield 'font list' => ['fonts.sans', 'Inter, sans-serif'];
        yield 'easing list' => ['easings.snappy', 'cubic-bezier(0.4, 0, 0.2, 1)'];
        yield 'composite shadow' => ['shadows.sm', '0px 1px 2px 0px black'];
        yield 'shadow list' => ['shadows.many', '0 1px red, inset  1px 2px 3px 4px blue'];
        yield 'composite border with a reference' => ['borders.thin', '1px solid var(--pd-colors-red-300)'];
        yield 'gradient' => ['gradients.sunset', 'linear-gradient(to right, red, blue)'];
        yield 'gradient with positions' => ['gradients.stops', 'radial-gradient(circle, red 0px, blue 100px)'];
        yield 'url asset' => ['assets.logo', 'url("/logo.svg")'];
        yield 'color mixed in a token' => ['colors.ghost', 'color-mix(in srgb, var(--pd-colors-red-300) 50%, transparent)'];
        yield 'reference' => ['colors.alias', 'var(--pd-colors-red-300)'];
        yield 'semantic token keeps its last condition' => ['colors.primary', 'var(--pd-colors-foo-bar)'];
        yield 'semantic token with one value' => ['colors.text', 'var(--pd-colors-red-300)'];
        yield 'semantic color mix' => ['colors.mixed', 'color-mix(in srgb, var(--pd-colors-red-300) 50%, transparent)'];
        yield 'negative spacing' => ['spacing.-px', 'calc(var(--pd-spacing-px) * -1)'];
        yield 'virtual token' => ['colors.colorPalette.300', 'colors.colorPalette.300'];
        yield 'empty token' => ['empty.gone', ''];
        yield 'unknown token' => ['colors.nope', null];
    }

    #[DataProvider('provideValues')]
    public function testGetValue(string $name, string|int|float|null $expected): void
    {
        $tokens = self::createTokens();

        $this->assertSame($expected, $tokens->getValue($name));
    }

    public static function provideSvgAssets(): iterable
    {
        yield 'panda example' => ['<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 16"><path stroke="white" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h8"/></svg>', 'url("data:image/svg+xml,%3csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 16 16\'%3e%3cpath stroke=\'white\' stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M4 8h8\'/%3e%3c/svg%3e")'];
        yield 'byte order mark, whitespace and color names' => ["\u{FEFF}<svg fill=\"#FF0000\" stroke=\"#fff\">\n  <path d=\"M0 0\"/>  </svg>", 'url("data:image/svg+xml,%3csvg fill=\'red\' stroke=\'white\'%3e %3cpath d=\'M0 0\'/%3e %3c/svg%3e")'];
        yield 'characters left as is by encodeURIComponent' => ['<svg><text>héllo (1) & *ok* ~!</text><rect fill="#000000ff" stroke="#0fff0"/></svg>', 'url("data:image/svg+xml,%3csvg%3e%3ctext%3eh%c3%a9llo (1) %26 *ok* ~!%3c/text%3e%3crect fill=\'black\' stroke=\'%230fff0\'/%3e%3c/svg%3e")'];
        yield 'first matching color name' => ['<svg fill="#00ffff" a="#0f0" b="#ffffff1"/>', 'url("data:image/svg+xml,%3csvg fill=\'aqua\' a=\'lime\' b=\'%23ffffff1\'/%3e")'];
    }

    #[DataProvider('provideSvgAssets')]
    public function testSvgAssetsBecomeDataUris(string $svg, string $expected): void
    {
        $tokens = new Tokens(['assets' => ['icon' => ['value' => ['type' => 'svg', 'value' => $svg]]]]);

        $this->assertSame($expected, $tokens->getValue('assets.icon'));
    }

    public function testGetVars(): void
    {
        $vars = self::createTokens()->getVars();

        $this->assertSame(['base', '_dark', 'lg'], array_keys($vars));
        $this->assertSame(['--pd-colors-primary' => 'var(--pd-colors-foo-bar)'], $vars['_dark']);
        $this->assertSame(['--pd-spacing-gutter' => 'var(--pd-spacing-px)'], $vars['lg']);
        $this->assertSame([
            '--pd-colors-red-300' => '#f00',
            '--pd-colors-red' => 'red',
            '--pd-colors-foo-bar' => 'blue',
            '--pd-colors-ghost' => 'color-mix(in srgb, var(--pd-colors-red-300) 50%, transparent)',
            '--pd-colors-alias' => 'var(--pd-colors-red-300)',
            '--pd-spacing-0' => '0rem',
            '--pd-spacing-4' => '1rem',
            '--pd-spacing-1\.5' => '0.375rem',
            '--pd-spacing-px' => '1px',
            '--pd-opacity-half' => 0.5,
            '--pd-opacity-seven' => 0.07,
            '--pd-fonts-sans' => 'Inter, sans-serif',
            '--pd-easings-snappy' => 'cubic-bezier(0.4, 0, 0.2, 1)',
            '--pd-shadows-sm' => '0px 1px 2px 0px black',
            '--pd-shadows-many' => '0 1px red, inset  1px 2px 3px 4px blue',
            '--pd-borders-thin' => '1px solid var(--pd-colors-red-300)',
            '--pd-gradients-sunset' => 'linear-gradient(to right, red, blue)',
            '--pd-gradients-stops' => 'radial-gradient(circle, red 0px, blue 100px)',
            '--pd-sizes-sm' => '24rem',
            '--pd-sizes-breakpoint-sm' => '640px',
            '--pd-sizes-breakpoint-md' => '768px',
            '--pd-assets-logo' => 'url("/logo.svg")',
            '--pd-breakpoints-sm' => '640px',
            '--pd-breakpoints-md' => '768px',
            '--pd-colors-primary' => 'var(--pd-colors-red-300)',
            '--pd-colors-text' => 'var(--pd-colors-red-300)',
            '--pd-colors-mixed' => 'color-mix(in srgb, var(--pd-colors-red-300) 50%, transparent)',
            '--pd-spacing-gutter' => 'var(--pd-spacing-4)',
        ], $vars['base']);
    }

    public function testConditionsOfAColorMixedSemanticTokenAreKept(): void
    {
        $tokens = new Tokens(
            ['colors' => ['red' => ['value' => '#f00'], 'blue' => ['value' => '#00f']]],
            ['colors' => ['overlay' => ['value' => ['base' => '{colors.red/50}', '_dark' => '{colors.blue}']]]],
        );

        $this->assertSame(['--colors-overlay' => 'var(--colors-blue)'], $tokens->getVars()['_dark']);
    }

    public function testHas(): void
    {
        $tokens = self::createTokens();

        $this->assertTrue($tokens->has('breakpoints.sm'));
        $this->assertTrue($tokens->has('empty.gone'));
        $this->assertFalse($tokens->has('breakpoints.xl'));
    }

    public function testGetColorPalette(): void
    {
        $tokens = self::createTokens();

        $this->assertSame([
            '--pd-colors-color-palette-300' => 'var(--pd-colors-red-300)',
            '--pd-colors-color-palette' => 'var(--pd-colors-red)',
        ], $tokens->getColorPalette('red'));
        $this->assertSame([
            '--pd-colors-color-palette' => 'var(--pd-colors-foo-bar)',
        ], $tokens->getColorPalette('fooBar'));
        $this->assertSame(
            ['red', 'fooBar', 'ghost', 'alias', 'primary', 'text', 'mixed'],
            $tokens->getColorPaletteNames(),
        );
        $this->assertNull($tokens->getColorPalette('nope'));
    }

    public static function provideReferences(): iterable
    {
        yield 'curly reference' => ['{colors.red.300}', 'var(--pd-colors-red-300)'];
        yield 'token function' => ['token(colors.red.300)', 'var(--pd-colors-red-300)'];
        yield 'missing token with a fallback' => ['token(colors.nope, blue)', 'blue'];
        yield 'nested fallback' => ['token(colors.red.300, token(colors.fooBar, red))', 'var(--pd-colors-red-300, var(--pd-colors-foo-bar, red))'];
        yield 'reference inside a value' => ['1px solid {colors.red.300}', '1px solid var(--pd-colors-red-300)'];
        yield 'missing token is escaped' => ['token(spacing.4) token(spacing.nope)', 'var(--pd-spacing-4) spacing\.nope'];
        yield 'color mix with an opacity token' => ['token(colors.red.300/half)', 'color-mix(in srgb, var(--pd-colors-red-300) 50%, transparent)'];
        yield 'color mix with a percentage' => ['{colors.red.300/40}', 'color-mix(in srgb, var(--pd-colors-red-300) 40%, transparent)'];
        yield 'no reference' => ['a.b', 'a.b'];
        yield 'css variable fallback' => ['token(colors.red.300, var(--x, var(--y, red)))', 'var(--pd-colors-red-300, var(--x, var(--y, red)))'];
    }

    #[DataProvider('provideReferences')]
    public function testExpandReferences(string $value, string $expected): void
    {
        $tokens = self::createTokens();

        $this->assertSame($expected, $tokens->expandReferences($value));
    }

    public static function provideResolvedReferences(): iterable
    {
        yield 'curly reference' => ['{sizes.sm}', '24rem'];
        yield 'token function' => ['token(spacing.4)', '1rem'];
        yield 'missing token with a fallback' => ['token(spacing.nope, 2px)', 'var(spacing\.nope, \32px)'];
        yield 'inside a media query' => ['(min-width: token(breakpoints.md))', '(min-width: 768px)'];
        yield 'number' => ['{opacity.half}', '0.5'];
        yield 'missing token is escaped' => ['{nope.x} and {colors.red.300}', 'nope\.x and #f00'];
        yield 'reference to a reference' => ['token(colors.alias, red)', 'var(--pd-colors-red-300, red)'];
        yield 'no reference' => ['no ref', 'no ref'];
    }

    #[DataProvider('provideResolvedReferences')]
    public function testResolveReferences(string $value, string $expected): void
    {
        $tokens = self::createTokens();

        $this->assertSame($expected, $tokens->resolveReferences($value));
    }

    public function testExpandReferencesRejectsInvalidColorMix(): void
    {
        $tokens = self::createTokens();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid color mix at colors.red.300/abc: colors.red.300');

        $tokens->expandReferences('{colors.red.300/abc}');
    }

    public static function provideColorMixes(): iterable
    {
        yield 'opacity token' => ['red.300/half', ['invalid' => false, 'color' => 'var(--pd-colors-red-300)', 'value' => 'color-mix(in srgb, var(--pd-colors-red-300) 50%, transparent)']];
        yield 'float opacity token' => ['red.300/seven', ['invalid' => false, 'color' => 'var(--pd-colors-red-300)', 'value' => 'color-mix(in srgb, var(--pd-colors-red-300) 7.000000000000001%, transparent)']];
        yield 'percentage' => ['red.300/40', ['invalid' => false, 'color' => 'var(--pd-colors-red-300)', 'value' => 'color-mix(in srgb, var(--pd-colors-red-300) 40%, transparent)']];
        yield 'decimal percentage' => ['fooBar/12.5', ['invalid' => false, 'color' => 'var(--pd-colors-foo-bar)', 'value' => 'color-mix(in srgb, var(--pd-colors-foo-bar) 12.5%, transparent)']];
        yield 'zero' => ['red.300/0', ['invalid' => false, 'color' => 'var(--pd-colors-red-300)', 'value' => 'color-mix(in srgb, var(--pd-colors-red-300) 0%, transparent)']];
        yield 'unknown color' => ['nope/50', ['invalid' => false, 'color' => 'nope', 'value' => 'color-mix(in srgb, nope 50%, transparent)']];
        yield 'no opacity' => ['red.300', ['invalid' => true, 'value' => 'red.300']];
        yield 'invalid opacity' => ['red.300/abc', ['invalid' => true, 'value' => 'red.300']];
        yield 'no color' => ['/50', ['invalid' => true, 'value' => '']];
    }

    #[DataProvider('provideColorMixes')]
    public function testColorMix(string $value, array $expected): void
    {
        $tokens = self::createTokens();

        $this->assertSame($expected, $tokens->colorMix($value));
    }

    public function testWithoutPrefix(): void
    {
        $tokens = new Tokens(['colors' => ['red' => ['value' => 'red']]]);

        $this->assertSame('var(--colors-red)', $tokens->getVar('colors.red'));
    }

    public function testTokensReferencingEachOtherAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "colors.a" token references itself.');

        new Tokens(['colors' => ['a' => ['value' => '{colors.b}'], 'b' => ['value' => '{colors.a}']]]);
    }

    public function testHashedVariablesAreNotSupported(): void
    {
        $this->expectException(UnsupportedStyleException::class);

        new Tokens(hash: true);
    }

    public function testATokenWithAnExternalVariableIsReadThroughIt(): void
    {
        $tokens = self::externalTokens();

        $variable = $tokens->getVar('colors.action.primary');

        $this->assertSame('var(--dt-color-action-primary)', $variable);
    }

    public function testATokenWithAnExternalVariableDeclaresNothing(): void
    {
        $tokens = self::externalTokens();

        $declared = array_merge(...array_values($tokens->getVars()));

        $this->assertArrayNotHasKey('--dt-color-action-primary', $declared);
        $this->assertArrayNotHasKey('--colors-action-primary', $declared);
    }

    public function testTheNegativeOfAnExternalSpacingUsesItsVariable(): void
    {
        $tokens = self::externalTokens();

        $negative = $tokens->getVar('spacing.-md');

        $this->assertSame('calc(var(--dt-dimension-spacing-md) * -1)', $negative);
    }

    private static function createTokens(): Tokens
    {
        $tokens = [
            'colors' => [
                'red' => ['300' => ['value' => '#f00'], 'DEFAULT' => ['value' => 'red']],
                'fooBar' => ['value' => 'blue'],
                'ghost' => ['value' => '{colors.red.300/50}'],
                'alias' => ['value' => '{colors.red.300}'],
            ],
            'spacing' => [
                '0' => ['value' => '0rem'],
                '4' => ['value' => '1rem'],
                '1.5' => ['value' => '0.375rem'],
                'px' => ['value' => '1px'],
            ],
            'opacity' => ['half' => ['value' => 0.5], 'seven' => ['value' => 0.07]],
            'fonts' => ['sans' => ['value' => ['Inter', 'sans-serif']]],
            'easings' => ['snappy' => ['value' => [0.4, 0, 0.2, 1]]],
            'shadows' => [
                'sm' => ['value' => ['offsetX' => 0, 'offsetY' => 1, 'blur' => 2, 'spread' => 0, 'color' => 'black']],
                'many' => ['value' => [
                    '0 1px red',
                    [
                        'offsetX' => 1,
                        'offsetY' => 2,
                        'blur' => 3,
                        'spread' => '4px',
                        'color' => 'blue',
                        'inset' => true,
                    ],
                ]],
            ],
            'borders' => ['thin' => ['value' => ['width' => 1, 'style' => 'solid', 'color' => '{colors.red.300}']]],
            'gradients' => [
                'sunset' => ['value' => ['type' => 'linear', 'placement' => 'to right', 'stops' => ['red', 'blue']]],
                'stops' => ['value' => [
                    'type' => 'radial',
                    'placement' => 'circle',
                    'stops' => [['color' => 'red', 'position' => 0], ['color' => 'blue', 'position' => 100]],
                ]],
            ],
            'sizes' => ['sm' => ['value' => '24rem']],
            'assets' => ['logo' => ['value' => ['type' => 'url', 'value' => '/logo.svg']]],
            'empty' => ['gone' => ['value' => '']],
        ];
        $semanticTokens = [
            'colors' => [
                'primary' => ['value' => ['base' => '{colors.red.300}', '_dark' => '{colors.fooBar}']],
                'text' => ['value' => '{colors.red.300}'],
                'mixed' => ['value' => '{colors.red.300/half}'],
            ],
            'spacing' => ['gutter' => ['value' => ['base' => '{spacing.4}', 'lg' => '{spacing.px}']]],
        ];

        return new Tokens($tokens, $semanticTokens, ['sm' => '640px', 'md' => '768px'], 'pd');
    }

    private static function externalTokens(): Tokens
    {
        $external = static fn (string $variable): array => [
            'value' => 'var('.$variable.')',
            'extensions' => ['externalVar' => $variable],
        ];

        return new Tokens([
            'colors' => ['action' => ['primary' => $external('--dt-color-action-primary')]],
            'spacing' => ['md' => $external('--dt-dimension-spacing-md')],
        ]);
    }
}
