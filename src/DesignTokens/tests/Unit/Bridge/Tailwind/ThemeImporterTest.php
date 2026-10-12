<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Unit\Bridge\Tailwind;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Symfony\UX\DesignTokens\Bridge\Tailwind\ThemeGenerator;
use Symfony\UX\DesignTokens\Bridge\Tailwind\ThemeImporter;
use Symfony\UX\DesignTokens\Resolver\TokenTreeBuilder;
use Symfony\UX\DesignTokens\Tests\Fixtures\RecordingLogger;

#[CoversClass(ThemeImporter::class)]
final class ThemeImporterTest extends TestCase
{
    private ThemeImporter $importer;
    private RecordingLogger $logger;

    protected function setUp(): void
    {
        $this->importer = new ThemeImporter();
        $this->logger = new RecordingLogger();
        $this->importer->setLogger($this->logger);
    }

    public function testImportsSupportedNamespacesAndKeepsFlatNames(): void
    {
        $tokens = $this->importer->import(<<<'CSS'
            @import "tailwindcss";
            @theme static inline {
              --*: initial;
              --color-*: initial;
              --color-brand-primary: oklch(72% 0.11 221.19 / 80%);
              --font-body: "Inter var", sans-serif;
              --font-weight-strong: 700;
              --text-title: 2rem;
              --tracking-tight: -0.02rem;
              --leading-copy: 1.5;
              --breakpoint-wide: 80rem;
              --container-card: 40rem;
              --spacing: 4px;
              --radius-card: 12px;
              --blur-soft: 8px;
              --perspective-near: 500px;
              --zoom-compact: 0.9;
              --aspect-photo: 1.5;
              --ease-snappy: cubic-bezier(0.2, 0, 0, 1);
            }
            CSS);

        self::assertSame('color', $tokens['color']['brand-primary']['$type']);
        self::assertSame([
            'colorSpace' => 'oklch',
            'components' => [0.72, 0.11, 221.19],
            'alpha' => 0.8,
        ], $tokens['color']['brand-primary']['$value']);
        self::assertSame(['Inter var', 'sans-serif'], $tokens['font']['body']['$value']);
        self::assertSame(700, $tokens['font-weight']['strong']['$value']);
        self::assertSame(['value' => 2, 'unit' => 'rem'], $tokens['text']['title']['$value']);
        self::assertSame(['value' => -0.02, 'unit' => 'rem'], $tokens['tracking']['tight']['$value']);
        self::assertSame(1.5, $tokens['leading']['copy']['$value']);
        self::assertSame(['value' => 4, 'unit' => 'px'], $tokens['spacing']['$root']['$value']);
        self::assertSame([0.2, 0, 0, 1], $tokens['ease']['snappy']['$value']);

        new TokenTreeBuilder()->resolve($tokens);
    }

    public function testImportsColorsShadowsAliasesAndMultipleThemeBlocks(): void
    {
        $tokens = $this->importer->import(<<<'CSS'
            /* @theme { --color-fake: red; } */
            @theme {
              --color-base: #369c;
              --color-rgb: rgb(51 102 204 / 50%);
              --color-p3: color(display-p3 20% 0.4 0.8);
              --shadow-card: 0 2px 4px #336699, 0 1px 2px 0 rgb(0 0 0 / 10%);
              --inset-shadow-control: inset 0 0 2px color(srgb 0.2 0.4 0.8);
              @keyframes ignored { from { opacity: 0; } to { opacity: 1; } }
            }
            @theme inline {
              --color-brand: var(--color-base);
              --color-base: #336699;
            }
            CSS);

        self::assertSame('#336699', $tokens['color']['base']['$value']['hex']);
        self::assertSame('{color.base}', $tokens['color']['brand']['$value']);
        self::assertSame([0.2, 0.4, 0.8], $tokens['color']['rgb']['$value']['components']);
        self::assertSame([0.2, 0.4, 0.8], $tokens['color']['p3']['$value']['components']);
        self::assertCount(2, $tokens['shadow']['card']['$value']);
        self::assertTrue($tokens['inset-shadow']['control']['$value']['inset']);

        $resolved = new TokenTreeBuilder()->resolve($tokens);
        self::assertSame($resolved['color']['base']->getValue(), $resolved['color']['brand']->getValue());
    }

    public function testImportsColorFormsFontWeightsAndEscapedStrings(): void
    {
        $tokens = $this->importer->import(<<<'CSS'
            @theme {
              /* declarations may contain comments */
              --color-short: #abc;
              --color-alpha: #1234;
              --color-long-alpha: #11223344;
              --color-percent-rgb: rgb(20% 40% 80%);
              --color-hsl: hsl(120 50% 25%);
              --color-none: oklab(none 0.1 -0.1);
              --font-escaped: "A\" B", sans-serif;
              --font-weight-semi: semibold;
              --font-weight-heavy: heavy;
              --tracking-zero: 0;
              --spacing-zero: 0;
              @custom-media --ignored;
            }
            CSS);

        self::assertSame('#aabbcc', $tokens['color']['short']['$value']['hex']);
        self::assertArrayHasKey('alpha', $tokens['color']['alpha']['$value']);
        self::assertArrayHasKey('alpha', $tokens['color']['long-alpha']['$value']);
        self::assertSame([0.2, 0.4, 0.8], $tokens['color']['percent-rgb']['$value']['components']);
        self::assertSame([120, 50, 25], $tokens['color']['hsl']['$value']['components']);
        self::assertSame('none', $tokens['color']['none']['$value']['components'][0]);
        self::assertSame(['A" B', 'sans-serif'], $tokens['font']['escaped']['$value']);
        self::assertSame('semi-bold', $tokens['font-weight']['semi']['$value']);
        self::assertSame('heavy', $tokens['font-weight']['heavy']['$value']);
        self::assertSame(['value' => 0, 'unit' => 'px'], $tokens['tracking']['zero']['$value']);
        self::assertSame(['value' => 0, 'unit' => 'px'], $tokens['spacing']['zero']['$value']);

        new TokenTreeBuilder()->resolve($tokens);
    }

    public function testRoundTripsTheTailwindExporterSubset(): void
    {
        $source = [
            'color' => ['brand' => ['$type' => 'color', '$value' => ['colorSpace' => 'srgb', 'components' => [0.2, 0.4, 0.8]]]],
            'font' => ['body' => ['$type' => 'fontFamily', '$value' => ['Inter', 'sans-serif']]],
            'weight' => ['strong' => ['$type' => 'fontWeight', '$value' => 700]],
            'spacing' => [
                '$root' => ['$type' => 'dimension', '$value' => ['value' => 0.25, 'unit' => 'rem']],
                'base' => ['$type' => 'dimension', '$value' => ['value' => 8, 'unit' => 'px']],
                'md' => ['$type' => 'dimension', '$value' => ['value' => 16, 'unit' => 'px']],
            ],
            'text' => ['sm' => ['$type' => 'dimension', '$value' => ['value' => 0.875, 'unit' => 'rem']]],
            'tracking' => ['tight' => ['$type' => 'number', '$value' => -0.02]],
            'easing' => ['snappy' => ['$type' => 'cubicBezier', '$value' => [0.2, 0, 0, 1]]],
            'elevation' => ['card' => ['$type' => 'shadow', '$value' => [
                'color' => ['colorSpace' => 'srgb', 'components' => [0.1, 0.2, 0.3]],
                'offsetX' => ['value' => 0, 'unit' => 'px'],
                'offsetY' => ['value' => 2, 'unit' => 'px'],
                'blur' => ['value' => 4, 'unit' => 'px'],
                'spread' => ['value' => 0, 'unit' => 'px'],
                'inset' => false,
            ]]],
        ];
        $resolver = new TokenTreeBuilder();
        $css = new ThemeGenerator()->generate($resolver->resolve($source));
        $roundTrip = $resolver->resolve($this->importer->import($css));

        self::assertSame($source['color']['brand']['$value'], $roundTrip['color']['brand']->getValue());
        self::assertSame($source['font']['body']['$value'], $roundTrip['font']['body']->getValue());
        self::assertSame(700, $roundTrip['font-weight']['strong']->getValue());
        self::assertSame(['value' => 16, 'unit' => 'px'], $roundTrip['spacing']['md']->getValue());
        self::assertSame(['value' => 0.25, 'unit' => 'rem'], $roundTrip['spacing']['$root']->getValue());
        self::assertSame(['value' => 8, 'unit' => 'px'], $roundTrip['spacing']['base']->getValue());
        self::assertSame(['value' => 0.875, 'unit' => 'rem'], $roundTrip['text']['sm']->getValue());
        self::assertSame(-0.02, $roundTrip['tracking']['tight']->getValue());
        self::assertSame([0.2, 0, 0, 1], $roundTrip['ease']['snappy']->getValue());
        self::assertSame($source['elevation']['card']['$value'], $roundTrip['shadow']['card']->getValue());
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidCss(): iterable
    {
        yield 'no theme' => [':root { --color-brand: red; }', 'No top-level'];
        yield 'nested theme' => ['@media (width > 10px) { @theme { --color-brand: #fff; } }', 'No top-level'];
        yield 'unterminated leading comment' => ['/* comment', 'Unterminated CSS comment'];
        yield 'bad options' => ['@theme prefix(tw) { --color-brand: #fff; }', 'Unsupported @theme options'];
        yield 'theme without block' => ['@theme;', 'Malformed Tailwind @theme block'];
        yield 'unterminated block' => ['@theme { --color-brand: #fff;', 'Unterminated'];
        yield 'unterminated block comment' => ['@theme { /* comment', 'Unterminated CSS comment'];
        yield 'unterminated string' => ['@theme { --font-body: "Inter; }', 'Unterminated CSS string'];
        yield 'unexpected rule' => ['@theme { color: red; }', 'Unexpected content'];
        yield 'missing colon' => ['@theme { --color-brand }', 'Malformed Tailwind theme declaration'];
        yield 'invalid variable name' => ['@theme { --color.bad: #fff; }', 'Invalid Tailwind theme variable'];
        yield 'brace in value' => ['@theme { --font-body: Inter{}; }', 'Malformed Tailwind theme declaration'];
        yield 'unterminated inner at-rule' => ['@theme { @custom-media --narrow }', 'Unterminated at-rule'];
    }

    #[DataProvider('skippedVariables')]
    public function testLogsAVariableItCannotImport(string $css, string $reason, string $level = LogLevel::WARNING): void
    {
        $tokens = $this->importer->import($css);

        self::assertSame([], $tokens);
        self::assertCount(1, $this->logger->records);
        self::assertSame($level, $this->logger->records[0]['level']);
        self::assertStringContainsString($reason, $this->logger->records[0]['message']);
    }

    /** @return iterable<string, array{0: string, 1: string, 2?: string}> */
    public static function skippedVariables(): iterable
    {
        yield 'unknown namespace' => ['@theme { --animate-spin: spin 1s; }', 'has no DTCG type', LogLevel::NOTICE];
        yield 'non-reset wildcard' => ['@theme { --color-*: #fff; }', 'Unsupported Tailwind theme variable', LogLevel::NOTICE];
        yield 'modifier' => ['@theme { --text-xs--line-height: 1; }', 'modifies another variable', LogLevel::NOTICE];
        yield 'unknown alias' => ['@theme { --color-brand: var(--external); }', 'references unknown'];
        yield 'expression' => ['@theme { --spacing-fluid: calc(1rem + 1vw); }', 'cannot be represented losslessly'];
        yield 'unsupported unit' => ['@theme { --spacing-fluid: 1vw; }', 'allows px and rem'];
        yield 'bad number' => ['@theme { --zoom-fluid: infinite; }', 'finite number'];
        yield 'named color' => ['@theme { --color-brand: red; }', 'structured CSS color'];
        yield 'bad hex length' => ['@theme { --color-brand: #12345; }', 'invalid hexadecimal'];
        yield 'empty color' => ['@theme { --color-brand: oklch(); }', 'has no value'];
        yield 'short rgb' => ['@theme { --color-brand: rgb(1 2); }', 'three color components'];
        yield 'unknown color space' => ['@theme { --color-brand: device-cmyk(0 0 0); }', 'unsupported color space'];
        yield 'short structured color' => ['@theme { --color-brand: oklch(0.5 0.2); }', 'three color components'];
        yield 'too many alpha separators' => ['@theme { --color-brand: oklch(50% 0.2 20 / 1 / 1); }', 'one alpha separator'];
        yield 'malformed color function' => ['@theme { --color-brand: color(srgb); }', 'malformed color()'];
        yield 'font function' => ['@theme { --font-body: env(font); }', 'unsupported font family'];
        yield 'empty font family' => ['@theme { --font-body: Inter, ; }', 'empty font family'];
        yield 'bad font weight' => ['@theme { --font-weight-heavy: 1200; }', 'not a DTCG font weight'];
        yield 'bad bezier' => ['@theme { --ease-snap: ease-in; }', 'cubic-bezier'];
        yield 'short bezier' => ['@theme { --ease-snap: cubic-bezier(0, 1); }', 'four cubic Bezier'];
        yield 'bad shadow' => ['@theme { --shadow-card: 0 2px red; }', 'px or rem'];
        yield 'shadow without color' => ['@theme { --shadow-card: 0 2px 4px; }', 'not a lossless CSS box shadow'];
        yield 'shadow with two colors' => ['@theme { --shadow-card: 0 2px #fff #000; }', 'multiple shadow colors'];
        yield 'shadow with too many dimensions' => ['@theme { --shadow-card: 0 1px 2px 3px 4px #000; }', 'not a lossless CSS box shadow'];
    }

    #[DataProvider('invalidCss')]
    public function testRejectsAFileThatIsNotATailwindTheme(string $css, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $this->importer->import($css);
    }

    public function testAcceptsEveryThemeOption(): void
    {
        foreach (['default', 'reference', 'static', 'inline', 'default inline reference'] as $options) {
            $tokens = $this->importer->import("@theme {$options} { --color-a: #fff; }");

            self::assertArrayHasKey('a', $tokens['color'], $options);
        }
    }

    public function testTheLastDeclarationMayOmitItsSemicolon(): void
    {
        self::assertArrayHasKey('b', $this->importer->import('@theme { --color-a: #fff; --color-b: #000 }')['color']);
    }

    public function testInsetShadowsKeepTheirOwnGroup(): void
    {
        $tokens = $this->importer->import('@theme { --shadow-xs: 0 1px 2px #0000000d; --inset-shadow-xs: inset 0 1px 1px #0000000d; }');

        self::assertArrayHasKey('xs', $tokens['shadow']);
        self::assertArrayHasKey('xs', $tokens['inset-shadow']);
    }

    public function testImportsTabSizes(): void
    {
        self::assertSame(4, $this->importer->import('@theme { --tab-size-code: 4; }')['tab-size']['code']['$value']);
    }

    public function testSkippedVariablesAreLoggedByName(): void
    {
        $tokens = $this->importer->import('@theme default { --color-a: #fff; --animate-spin: spin 1s linear infinite; --text-xs--line-height: calc(1 / 0.75); --font-sans--font-feature-settings: normal; --text-shadow-xs: 0 1px 0 #000; --tracking-tight: -0.025em; --default-font-family: --theme(--font-sans, initial); }');

        self::assertSame(['a'], array_keys($tokens['color']));
        self::assertSame(
            ['--animate-spin', '--text-xs--line-height', '--font-sans--font-feature-settings', '--text-shadow-xs', '--default-font-family', '--tracking-tight'],
            array_map(static fn (array $record): mixed => $record['context']['variable'] ?? null, $this->logger->records),
        );
    }

    public function testImportsWithoutALogger(): void
    {
        $tokens = new ThemeImporter()->import('@theme { --color-a: #fff; --font-sans--font-feature-settings: normal; }');

        self::assertSame(['color'], array_keys($tokens));
        self::assertSame(['a'], array_keys($tokens['color']));
    }
}
