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
use PHPUnit\Framework\TestCase;
use Symfony\UX\DesignTokens\Bridge\Tailwind\ThemeGenerator;
use Symfony\UX\DesignTokens\Resolver\TokenTreeBuilder;
use Symfony\UX\DesignTokens\Tests\Fixtures\TokenValues;

#[CoversClass(ThemeGenerator::class)]
final class ThemeGeneratorTest extends TestCase
{
    private ThemeGenerator $generator;
    private TokenTreeBuilder $resolver;

    protected function setUp(): void
    {
        $this->generator = new ThemeGenerator();
        $this->resolver = new TokenTreeBuilder();
    }

    public function testGeneratesOfficialTailwindNamespaces(): void
    {
        $css = $this->generate([
            'color' => ['brand' => ['primary' => ['$type' => 'color', '$value' => TokenValues::color()]]],
            'font' => ['body' => ['$type' => 'fontFamily', '$value' => ['Inter', 'sans-serif']]],
            'weight' => ['strong' => ['$type' => 'fontWeight', '$value' => 700]],
            'spacing' => ['md' => ['$type' => 'dimension', '$value' => TokenValues::dimension(16)]],
            'radius' => ['card' => ['$type' => 'dimension', '$value' => TokenValues::dimension(12)]],
            'breakpoints' => ['wide' => ['$type' => 'dimension', '$value' => ['value' => 80, 'unit' => 'rem']]],
            'tracking' => ['tight' => ['$type' => 'number', '$value' => -0.02]],
            'easing' => ['snappy' => ['$type' => 'cubicBezier', '$value' => [0.2, 0, 0, 1]]],
            'elevation' => ['card' => ['$type' => 'shadow', '$value' => TokenValues::shadow()]],
            'inset-shadow' => ['control' => ['$type' => 'shadow', '$value' => TokenValues::shadow(true)]],
        ]);

        self::assertStringStartsWith("@theme static {\n", $css);
        self::assertStringContainsString('--color-brand-primary: color(srgb 0.2 0.4 0.8);', $css);
        self::assertStringContainsString('--font-body: Inter, sans-serif;', $css);
        self::assertStringContainsString('--font-weight-strong: 700;', $css);
        self::assertStringContainsString('--spacing-md: 16px;', $css);
        self::assertStringContainsString('--radius-card: 12px;', $css);
        self::assertStringContainsString('--breakpoint-wide: 80rem;', $css);
        self::assertStringContainsString('--tracking-tight: -0.02;', $css);
        self::assertStringContainsString('--ease-snappy: cubic-bezier(0.2, 0, 0, 1);', $css);
        self::assertStringContainsString('--shadow-card:', $css);
        self::assertStringContainsString('--inset-shadow-control:', $css);
        self::assertStringEndsWith("}\n", $css);
    }

    public function testExpandsTypographyIntoTailwindThemeVariables(): void
    {
        $css = $this->generate(['typography' => ['heading' => ['$type' => 'typography', '$value' => [
            'fontFamily' => ['Inter', 'sans-serif'],
            'fontSize' => ['value' => 2, 'unit' => 'rem'],
            'fontWeight' => 700,
            'letterSpacing' => ['value' => -0.02, 'unit' => 'rem'],
            'lineHeight' => 1.2,
        ]]]]);

        self::assertStringContainsString('--text-heading: 2rem;', $css);
        self::assertStringContainsString('--text-heading--line-height: 1.2;', $css);
        self::assertStringContainsString('--text-heading--letter-spacing: -0.02rem;', $css);
        self::assertStringContainsString('--text-heading--font-weight: 700;', $css);
        self::assertStringNotContainsString('--leading-heading', $css);
    }

    public function testUsesSpacingForAnUnclassifiedDimensionAndOmitsUnsupportedTypes(): void
    {
        $css = $this->generate([
            'size' => ['icon' => ['$type' => 'dimension', '$value' => TokenValues::dimension(24)]],
            'motion' => ['fast' => ['$type' => 'duration', '$value' => ['value' => 100, 'unit' => 'ms']]],
            'opacity' => ['muted' => ['$type' => 'number', '$value' => 0.5]],
        ]);

        self::assertStringContainsString('--spacing-icon: 24px;', $css);
        self::assertStringNotContainsString('motion', $css);
        self::assertStringNotContainsString('opacity', $css);
        self::assertSame("@theme static {\n}\n", $this->generator->generate([]));
    }

    public function testWritesTheSpacingScaleAndTextSizesUnderTheirOwnNames(): void
    {
        $css = $this->generate([
            'spacing' => ['$root' => ['$type' => 'dimension', '$value' => ['value' => 0.25, 'unit' => 'rem']]],
            'text' => ['sm' => ['$type' => 'dimension', '$value' => ['value' => 0.875, 'unit' => 'rem']]],
        ]);

        self::assertStringContainsString('  --spacing: 0.25rem;', $css);
        self::assertStringContainsString('  --text-sm: 0.875rem;', $css);
    }

    public function testRejectsNormalizedNameCollisionsAndUnsafeCssValues(): void
    {
        try {
            $this->generate([
                'color' => [
                    'Brand primary' => ['$type' => 'color', '$value' => TokenValues::color()],
                    'brand-primary' => ['$type' => 'color', '$value' => ['colorSpace' => 'srgb', 'components' => [1, 0, 0]]],
                ],
            ]);
            self::fail('Expected a Tailwind variable collision.');
        } catch (\InvalidArgumentException $error) {
            self::assertStringContainsString('same Tailwind theme variable', $error->getMessage());
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('unsafe Tailwind theme value');
        $this->generate(['font' => ['bad' => ['$type' => 'fontFamily', '$value' => 'Inter; color: red']]]);
    }

    public function testNamespacesComeFromAnySegmentOfThePath(): void
    {
        $css = $this->generate([
            'dimension' => ['$type' => 'dimension', 'radius' => ['control' => ['$value' => TokenValues::dimension(6)]], 'space' => ['md' => ['$value' => TokenValues::dimension(16)]]],
            'font' => [
                'size' => ['$type' => 'dimension', 'body' => ['$value' => ['value' => 1, 'unit' => 'rem']]],
                'weight' => ['$type' => 'fontWeight', 'regular' => ['$value' => 400]],
                'body' => ['$type' => 'fontFamily', '$value' => ['Inter', 'sans-serif']],
            ],
            'number' => ['$type' => 'number', 'tab-size' => ['code' => ['$value' => 4]], 'zoom' => ['compact' => ['$value' => 0.9]]],
        ]);

        self::assertStringContainsString('--radius-control: 6px;', $css);
        self::assertStringContainsString('--spacing-md: 16px;', $css);
        self::assertStringContainsString('--text-body: 1rem;', $css);
        self::assertStringContainsString('--font-weight-regular: 400;', $css);
        self::assertStringContainsString('--font-body: Inter, sans-serif;', $css);
        self::assertStringContainsString('--tab-size-code: 4;', $css);
        self::assertStringContainsString('--zoom-compact: 0.9;', $css);
    }

    public function testATypographyNextToAFontFamilyOfTheSameNameDoesNotCollide(): void
    {
        $css = $this->generate([
            'font' => ['body' => ['$type' => 'fontFamily', '$value' => ['Inter']]],
            'typography' => ['body' => ['$type' => 'typography', '$value' => [
                'fontFamily' => ['Inter'], 'fontSize' => ['value' => 1, 'unit' => 'rem'], 'fontWeight' => 400,
                'letterSpacing' => ['value' => 0, 'unit' => 'px'], 'lineHeight' => 1.5,
            ]]],
        ]);

        self::assertStringContainsString('--font-body: Inter;', $css);
        self::assertStringContainsString('--text-body--line-height: 1.5;', $css);
    }

    private function generate(array $tokens): string
    {
        return $this->generator->generate($this->resolver->resolve($tokens));
    }
}
