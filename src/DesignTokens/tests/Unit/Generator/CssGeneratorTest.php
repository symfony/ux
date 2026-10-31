<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Unit\Generator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\DesignTokens\Generator\CssGenerator;
use Symfony\UX\DesignTokens\Generator\GeneratorInterface;
use Symfony\UX\DesignTokens\Resolver\TokenTreeBuilder;
use Symfony\UX\DesignTokens\Tests\Fixtures\TokenValues;

#[CoversClass(CssGenerator::class)]
final class CssGeneratorTest extends TestCase
{
    /**
     * @param array<array-key, mixed> $document
     * @param list<string>            $contains
     * @param list<string>            $leavesOut
     */
    #[DataProvider('generatedCssProvider')]
    public function testGeneratesTheStylesheetOfADocument(array $document, array $contains, array $leavesOut = []): void
    {
        $css = new CssGenerator()->generate(new TokenTreeBuilder()->resolve($document));

        foreach ($contains as $fragment) {
            self::assertStringContainsString($fragment, $css);
        }
        foreach ($leavesOut as $fragment) {
            self::assertStringNotContainsString($fragment, $css);
        }
    }

    /** @return iterable<string, array{0: array<array-key, mixed>, 1: list<string>, 2?: list<string>}> */
    public static function generatedCssProvider(): iterable
    {
        $brand = ['$type' => 'color', '$value' => TokenValues::color()];

        yield 'an empty tree still has a :root block' => [[], [':root {']];
        yield 'one token in the :root block' => [
            ['ratio' => ['$type' => 'number', '$value' => 1.5]],
            [':root {', '--ratio: 1.5;'],
        ];
        yield 'several tokens' => [
            ['a' => ['$type' => 'number', '$value' => 1], 'b' => ['$type' => 'number', '$value' => 2]],
            ['--a: 1;', '--b: 2;'],
        ];
        yield '@property for typed tokens' => [
            [
                'color' => $brand,
                'size' => ['$type' => 'dimension', '$value' => TokenValues::dimension(16)],
                'duration' => ['$type' => 'duration', '$value' => TokenValues::dimension(300, 'ms')],
                'ratio' => ['$type' => 'number', '$value' => 1.5],
            ],
            ["syntax: '<color>'", "syntax: '<length>'", "syntax: '<time>'", "syntax: '<number>'", '--color: color(srgb 0.2 0.4 0.8);', '--size: 16px;'],
        ];
        yield 'token paths become custom property names' => [
            ['color' => ['brand' => ['primary' => $brand]]],
            ['--color-brand-primary'],
        ];
        yield 'names are normalized' => [
            ['Button background' => $brand],
            ['--Button-background:'],
        ];
        yield 'structured composite tokens are unrolled' => [
            [
                'text' => ['heading' => ['$type' => 'typography', '$value' => TokenValues::typography()]],
                'border' => ['default' => ['$type' => 'border', '$value' => TokenValues::border()]],
                'motion' => ['fade' => ['$type' => 'transition', '$value' => TokenValues::transition()]],
            ],
            ['--text-heading-font-family: Inter, sans-serif;', 'initial-value: 32px;', 'initial-value: 1px;', '--motion-fade-duration: 250ms;'],
        ];
        yield 'shadow sub-properties are unrolled' => [
            ['elevation' => ['sm' => ['$type' => 'shadow', '$value' => TokenValues::shadow()]]],
            [
                '--elevation-sm: 0px 2px 4px 0px color(srgb 0.2 0.4 0.8);',
                '--elevation-sm-color: color(srgb 0.2 0.4 0.8);',
                '--elevation-sm-offset-x: 0px;',
                '--elevation-sm-offset-y: 2px;',
                '--elevation-sm-blur: 4px;',
                '--elevation-sm-spread: 0px;',
            ],
        ];
        yield 'the layers of a multi-layer shadow are numbered' => [
            ['elevation' => ['lg' => ['$type' => 'shadow', '$value' => [TokenValues::shadow(), [...TokenValues::shadow(), 'offsetY' => TokenValues::dimension(8)]]]]],
            ['--elevation-lg-1-offset-y: 2px;', '--elevation-lg-2-offset-y: 8px;'],
            ['--elevation-lg-offset-y:'],
        ];
        yield 'typography letter spacing is unrolled' => [
            ['heading' => ['h1' => ['$type' => 'typography', '$value' => TokenValues::typography(['fontSize' => TokenValues::dimension(2, 'rem'), 'letterSpacing' => TokenValues::dimension(-0.5)])]]],
            ['--heading-h1-letter-spacing: -0.5px;', "@property --heading-h1-letter-spacing { syntax: '<length>'; inherits: true; initial-value: -0.5px; }"],
        ];
        yield 'font-relative lengths get no @property rule' => [
            [
                'space' => ['$type' => 'dimension', 'rem' => ['$value' => TokenValues::dimension(1.5, 'rem')], 'px' => ['$value' => TokenValues::dimension(4)]],
                'type' => ['$type' => 'typography', 'body' => ['$value' => TokenValues::typography(['fontSize' => TokenValues::dimension(1, 'rem')])]],
            ],
            ["@property --space-px { syntax: '<length>'", "@property --type-body-letter-spacing { syntax: '<length>'", '--space-rem: 1.5rem;'],
            ['@property --space-rem ', '@property --type-body-font-size '],
        ];
    }

    /**
     * @param array<string, mixed> $context
     * @param list<string>         $leavesOut
     */
    #[DataProvider('prefixProvider')]
    public function testPrefixesEveryCustomProperty(string $configured, array $context, string $expected, array $leavesOut): void
    {
        $tokens = new TokenTreeBuilder()->resolve([
            'color' => ['brand' => ['primary' => ['$type' => 'color', '$value' => TokenValues::color()]]],
        ]);

        $css = new CssGenerator($configured)->generate($tokens, $context);

        self::assertStringContainsString($expected, $css);
        foreach ($leavesOut as $fragment) {
            self::assertStringNotContainsString($fragment, $css);
        }
    }

    /** @return iterable<string, array{string, array<string, mixed>, string, list<string>}> */
    public static function prefixProvider(): iterable
    {
        yield 'the configured application prefix' => ['my', [], '--my-color-brand-primary', []];
        yield 'the context prefix replaces the configured one' => ['dt', [GeneratorInterface::CSS_PREFIX => 'app'], '--app-color-brand-primary', ['--dt-']];
    }

    public function testWritesOnlyWhatChangesInTheDarkScheme(): void
    {
        $border = static fn (array $color): array => ['$type' => 'border', '$value' => ['color' => $color, 'width' => TokenValues::dimension(1), 'style' => 'solid']];
        $light = new TokenTreeBuilder()->resolve([
            'space' => ['md' => ['$type' => 'dimension', '$value' => TokenValues::dimension(16)]],
            'surface' => ['$type' => 'color', '$value' => TokenValues::color(1, 1, 1)],
            'edge' => $border(TokenValues::color(0.9, 0.9, 0.9)),
        ]);
        $dark = new TokenTreeBuilder()->resolve([
            'space' => ['md' => ['$type' => 'dimension', '$value' => TokenValues::dimension(16)]],
            'surface' => ['$type' => 'color', '$value' => TokenValues::color(0.1, 0.1, 0.1)],
            'edge' => $border(TokenValues::color(0.3, 0.3, 0.3)),
            'glow' => ['$type' => 'color', '$value' => TokenValues::color(0, 1, 0)],
        ]);

        $css = new CssGenerator('dt')->generate($light, [GeneratorInterface::DARK_TOKENS => $dark]);
        [$root, $darkBlocks] = explode('@media (prefers-color-scheme: dark)', $css, 2);

        self::assertStringContainsString('--dt-space-md: 16px;', $root);
        self::assertStringStartsWith(" {\n  :root:not([data-theme=\"light\"]):not([data-theme=\"dark\"]) {\n    --dt-surface: color(srgb 0.1 0.1 0.1);", $darkBlocks);
        self::assertStringContainsString(':root[data-theme="dark"] {', $darkBlocks);
        self::assertStringContainsString('--dt-edge-color: color(srgb 0.3 0.3 0.3);', $darkBlocks);
        self::assertStringContainsString('--dt-glow: color(srgb 0 1 0);', $darkBlocks);
        self::assertStringNotContainsString('--dt-space-md', $darkBlocks);
        self::assertStringNotContainsString('--dt-edge-width', $darkBlocks);
        self::assertStringNotContainsString('@property', $darkBlocks);
        self::assertSame(1, substr_count($css, '@property --dt-surface '));
    }

    public function testWritesNoDarkBlockWhenNothingChanges(): void
    {
        $tokens = new TokenTreeBuilder()->resolve(['surface' => ['$type' => 'color', '$value' => TokenValues::color()]]);

        $css = new CssGenerator('dt')->generate($tokens, [GeneratorInterface::DARK_TOKENS => $tokens]);

        self::assertStringNotContainsString('prefers-color-scheme', $css);
        self::assertStringNotContainsString('data-theme', $css);
    }

    public function testRejectsTwoNamesThatProduceTheSameCustomProperty(): void
    {
        $tokens = new TokenTreeBuilder()->resolve([
            'Button background' => ['$type' => 'color', '$value' => TokenValues::color()],
            'Button-background' => ['$type' => 'color', '$value' => TokenValues::color()],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/same CSS custom property/');
        new CssGenerator()->generate($tokens);
    }
}
