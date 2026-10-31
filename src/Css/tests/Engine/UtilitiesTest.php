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
use Symfony\UX\Css\Engine\TransformArgs;
use Symfony\UX\Css\Engine\Utilities;
use Symfony\UX\Css\Exception\UnsupportedStyleException;

final class UtilitiesTest extends TestCase
{
    private Utilities $utilities;

    protected function setUp(): void
    {
        $this->utilities = new Utilities([
            'display' => ['className' => 'd'],
            'background' => ['className' => 'bg', 'shorthand' => 'bg', 'values' => 'colors'],
            'insetInlineEnd' => ['className' => 'inset-e', 'shorthand' => ['insetEnd', 'end']],
            'srOnly' => ['className' => 'sr', 'transform' => ['__function' => 'transform']],
        ]);
    }

    public function testShorthandsResolveToTheirProperty(): void
    {
        $this->assertSame('background', $this->utilities->resolveShorthand('bg'));
        $this->assertSame('insetInlineEnd', $this->utilities->resolveShorthand('insetEnd'));
        $this->assertSame('insetInlineEnd', $this->utilities->resolveShorthand('end'));
        $this->assertSame('color', $this->utilities->resolveShorthand('color'));
    }

    public function testClassNameComesFromTheUtility(): void
    {
        $this->assertSame('d', $this->utilities->getClassName('display'));
        $this->assertSame('bg', $this->utilities->getClassName('bg'));
        $this->assertSame('inset-e', $this->utilities->getClassName('end'));
    }

    public function testClassNameFallsBackToTheHyphenatedProperty(): void
    {
        $this->assertSame('white-space', $this->utilities->getClassName('whiteSpace'));
    }

    public static function provideTransforms(): iterable
    {
        yield 'token category' => ['p', 4, ['className' => 'p_4', 'styles' => ['padding' => 'var(--spacing-4)']]];
        yield 'negative token' => ['p', '-4', ['className' => 'p_-4', 'styles' => ['padding' => 'calc(var(--spacing-4) * -1)']]];
        yield 'unknown value' => ['p', '10px', ['className' => 'p_10px', 'styles' => ['padding' => '10px']]];
        yield 'spaces become underscores' => ['p', '1px  2px', ['className' => 'p_1px__2px', 'styles' => ['padding' => '1px  2px']]];
        yield 'reference' => ['p', '{spacing.4}', ['className' => 'p_{spacing.4}', 'styles' => ['padding' => 'var(--spacing-4)']]];
        yield 'arbitrary value' => ['p', '[4]', ['className' => 'p_[4]', 'styles' => ['padding' => 'var(--spacing-4)']]];
        yield 'function values' => ['margin', 'auto', ['className' => 'm_auto', 'styles' => ['margin' => 'auto']]];
        yield 'function values with a token' => ['margin', '4', ['className' => 'm_4', 'styles' => ['margin' => 'var(--spacing-4)']]];
        yield 'object values' => ['fontSmoothing', 'subpixel-antialiased', ['className' => 'font-smoothing_subpixel-antialiased', 'styles' => ['fontSmoothing' => 'auto']]];
        yield 'color mix' => ['bg', 'red.300/40', ['className' => 'bg_red.300/40', 'styles' => ['--mix-background' => 'color-mix(in srgb, var(--colors-red-300) 40%, transparent)', 'background' => 'var(--mix-background, var(--colors-red-300))']]];
        yield 'color token without mix' => ['bg', 'red.300', ['className' => 'bg_red.300', 'styles' => ['background' => 'var(--colors-red-300)']]];
        yield 'custom transform' => ['srOnly', true, ['className' => 'sr_true', 'styles' => ['position' => 'absolute']]];
        yield 'css variable resolves tokens' => ['--size', 'spacing.4', ['className' => '--size_spacing.4', 'styles' => ['--size' => 'var(--spacing-4)']]];
        yield 'color palette' => ['colorPalette', 'red', ['className' => 'color-palette_red', 'styles' => ['--colors-color-palette-300' => 'var(--colors-red-300)']]];
        yield 'layer' => ['display', 'flex', ['className' => 'd_flex', 'styles' => ['display' => 'flex'], 'layer' => 'base']];
    }

    #[DataProvider('provideTransforms')]
    public function testTransform(string $property, string|int|float|bool $value, array $expected): void
    {
        $tokens = new Tokens([
            'spacing' => ['4' => ['value' => '1rem']],
            'colors' => ['red' => ['300' => ['value' => '#f00']]],
        ]);
        $config = [
            'display' => ['className' => 'd', 'layer' => 'base'],
            'padding' => ['className' => 'p', 'shorthand' => 'p', 'values' => 'spacing'],
            'margin' => [
                'className' => 'm',
                'values' => [
                    '__function' => 'values',
                    'probe' => ['auto' => 'auto', '__category:spacing' => 'spacing'],
                ],
            ],
            'fontSmoothing' => ['values' => ['antialiased' => 'antialiased', 'subpixel-antialiased' => 'auto']],
            'background' => [
                'className' => 'bg',
                'shorthand' => 'bg',
                'values' => 'colors',
                'transform' => ['__function' => 'transform', 'colorMix' => 'background'],
            ],
            'srOnly' => [
                'className' => 'sr',
                'values' => ['type' => 'boolean'],
                'transform' => ['__function' => 'transform'],
            ],
        ];
        $transforms = [
            'srOnly' => static fn (bool $value, TransformArgs $args): array => [
                'position' => $value ? 'absolute' : 'static',
            ],
        ];
        $utilities = new Utilities($config, '_', $tokens, $transforms);

        $this->assertSame($expected, $utilities->transform($property, $value));
    }

    public function testTransformWithoutImplementationIsUnsupported(): void
    {
        $this->expectException(UnsupportedStyleException::class);
        $this->expectExceptionMessage('The "srOnly" utility relies on a transform, which is not supported yet.');

        $this->utilities->transform('srOnly', true);
    }
}
