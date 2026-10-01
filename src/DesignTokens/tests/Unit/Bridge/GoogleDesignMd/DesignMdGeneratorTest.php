<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Unit\Bridge\GoogleDesignMd;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;
use Symfony\UX\DesignTokens\Bridge\GoogleDesignMd\DesignMdGenerator;
use Symfony\UX\DesignTokens\Exception\InvalidArgumentException;
use Symfony\UX\DesignTokens\Generator\GeneratorInterface;
use Symfony\UX\DesignTokens\Resolver\TokenTreeBuilder;
use Symfony\UX\DesignTokens\Tests\Fixtures\TokenValues;

#[CoversClass(DesignMdGenerator::class)]
final class DesignMdGeneratorTest extends TestCase
{
    private DesignMdGenerator $generator;
    private TokenTreeBuilder $resolver;

    protected function setUp(): void
    {
        $this->generator = new DesignMdGenerator();
        $this->resolver = new TokenTreeBuilder();
    }

    public function testGenerateContainsTitleAndSections(): void
    {
        $md = $this->generate([
            'color' => ['primary' => ['$type' => 'color', '$value' => TokenValues::color()]],
            'spacing' => ['md' => ['$type' => 'dimension', '$value' => ['value' => 16, 'unit' => 'px']]],
            'motion' => ['fast' => ['$type' => 'duration', '$value' => ['value' => 100, 'unit' => 'ms']]],
            'opacity' => ['disabled' => ['$type' => 'number', '$value' => 0.5]],
            'elevation' => ['sm' => ['$type' => 'shadow', '$value' => [
                'offsetX' => ['value' => 0, 'unit' => 'px'],
                'offsetY' => ['value' => 1, 'unit' => 'px'],
                'blur' => ['value' => 2, 'unit' => 'px'],
                'spread' => ['value' => 0, 'unit' => 'px'],
                'color' => TokenValues::color(),
            ]]],
        ]);

        self::assertStringContainsString('version: alpha', $md);
        self::assertStringContainsString('name: \'Design System\'', $md);
        self::assertStringContainsString('# Design System', $md);
        self::assertStringNotContainsString('tokens across', $md);
        self::assertStringContainsString('## Colors', $md);
        self::assertStringContainsString("primary: 'rgb(20% 40% 80%)'", $md);
        self::assertStringContainsString('16px', $md);
        self::assertStringContainsString('## Elevation & Depth', $md);
        self::assertStringContainsString('`elevation.sm`', $md);
    }

    public function testGenerateRendersStructuredCompositeValues(): void
    {
        $md = $this->generate([
            'heading' => ['$type' => 'typography', '$value' => TokenValues::typography()],
            'border' => ['$type' => 'border', '$value' => TokenValues::border()],
            'transition' => ['$type' => 'transition', '$value' => TokenValues::transition()],
        ]);

        self::assertStringContainsString('## Typography', $md);
        self::assertStringContainsString('Inter, sans-serif', $md);
        self::assertStringContainsString('32px', $md);
        self::assertStringContainsString('## Additional Tokens', $md);
        self::assertStringContainsString('| `border` | `border` |', $md);
        self::assertStringContainsString('color(srgb 0.2 0.4 0.8)', $md);
        self::assertStringContainsString('| `transition` | `transition` |', $md);
        self::assertStringContainsString('250ms', $md);
    }

    public function testGenerateUsesStableSectionOrderAndCustomTitle(): void
    {
        $generator = new DesignMdGenerator();
        $md = $generator->generate($this->resolver->resolve([
            'spacing' => ['md' => ['$type' => 'dimension', '$value' => ['value' => 8, 'unit' => 'px']]],
            'color' => ['primary' => ['$type' => 'color', '$value' => TokenValues::color()]],
            'motion' => ['fast' => ['$type' => 'duration', '$value' => ['value' => 100, 'unit' => 'ms']]],
        ]), [GeneratorInterface::TITLE => 'Acme']);

        self::assertStringContainsString('# Acme', $md);
        self::assertLessThan(mb_strpos($md, '## Layout'), mb_strpos($md, '## Colors'));
        self::assertLessThan(mb_strpos($md, '## Additional Tokens'), mb_strpos($md, '## Layout'));
    }

    public function testGenerateEmptyTokensProducesHeader(): void
    {
        $md = $this->generator->generate([]);

        self::assertStringContainsString('# Design System', $md);
        self::assertStringContainsString('## Overview', $md);
    }

    public function testGenerateHasGoogleDesignMdFrontMatter(): void
    {
        $md = $this->generate([
            'color' => ['primary' => ['$type' => 'color', '$value' => TokenValues::color()]],
            'spacing' => ['md' => ['$type' => 'dimension', '$value' => ['value' => 16, 'unit' => 'px']]],
            'radius' => ['md' => ['$type' => 'dimension', '$value' => ['value' => 8, 'unit' => 'px']]],
            'components' => ['button-primary' => [
                'backgroundColor' => ['$type' => 'color', '$value' => TokenValues::color()],
                'padding' => ['$type' => 'dimension', '$value' => ['value' => 12, 'unit' => 'px']],
            ]],
        ]);

        self::assertSame(1, preg_match('/\A---\n(.*?)\n---\n/s', $md, $matches));

        /** @var array<string, mixed> $frontMatter */
        $frontMatter = Yaml::parse($matches[1]);
        self::assertSame('alpha', $frontMatter['version']);
        self::assertSame('Design System', $frontMatter['name']);
        self::assertSame('rgb(20% 40% 80%)', $frontMatter['colors']['primary']);
        self::assertSame('16px', $frontMatter['spacing']['md']);
        self::assertSame('8px', $frontMatter['rounded']['md']);
        self::assertSame('rgb(20% 40% 80%)', $frontMatter['components']['button-primary']['backgroundColor']);
        self::assertSame('12px', $frontMatter['components']['button-primary']['padding']);
    }

    public function testNamesKeepTheirWholePath(): void
    {
        $md = $this->generate(['color' => [
            '$type' => 'color',
            'palette' => ['code' => ['$value' => TokenValues::color()]],
            'content' => ['code' => ['$value' => TokenValues::color()]],
        ]]);

        self::assertStringContainsString("palette-code: 'rgb(20% 40% 80%)'", $md);
        self::assertStringContainsString("content-code: 'rgb(20% 40% 80%)'", $md);
    }

    public function testTwoPathsGivingTheSameNameAreRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Design tokens "color.a.b-c" and "color.a-b.c" both export to the DESIGN.md name "a-b-c".');

        $this->generate(['color' => [
            '$type' => 'color',
            'a' => ['b-c' => ['$value' => TokenValues::color()]],
            'a-b' => ['c' => ['$value' => TokenValues::color()]],
        ]]);
    }

    /**
     * @param array<string, mixed> $value
     */
    #[DataProvider('colors')]
    public function testColorsUseAFormDesignMdAccepts(array $value, string $expected): void
    {
        $md = $this->generate(['color' => ['$type' => 'color', 'x' => ['$value' => $value]]]);

        self::assertStringContainsString("x: '{$expected}'", $md);
    }

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function colors(): iterable
    {
        yield 'srgb' => [['colorSpace' => 'srgb', 'components' => [1, 0, 0]], 'rgb(100% 0% 0%)'];
        yield 'srgb with alpha' => [['colorSpace' => 'srgb', 'components' => [1, 0, 0], 'alpha' => 0.5], 'rgb(100% 0% 0% / 0.5)'];
        yield 'oklch' => [['colorSpace' => 'oklch', 'components' => [0.62, 0.18, 255]], 'oklch(62% 0.18 255)'];
        yield 'hsl' => [['colorSpace' => 'hsl', 'components' => [120, 50, 50]], 'hsl(120 50% 50%)'];
        yield 'display-p3 with hex fallback' => [['colorSpace' => 'display-p3', 'components' => [1, 0, 0], 'hex' => '#ff0000'], '#ff0000'];
    }

    public function testASpaceDesignMdCannotExpressNeedsAHexFallback(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Design token "color.x" uses the rec2020 color space, which DESIGN.md cannot express. Add a "hex" fallback.');

        $this->generate(['color' => ['$type' => 'color', 'x' => ['$value' => ['colorSpace' => 'rec2020', 'components' => [1, 0, 0]]]]]);
    }

    public function testTheBodyDoesNotRepeatTheFrontMatter(): void
    {
        $md = $this->generate([
            'color' => ['$type' => 'color', 'x' => ['$value' => TokenValues::color()]],
            'spacing' => ['md' => ['$type' => 'dimension', '$value' => ['value' => 16, 'unit' => 'px']]],
            'components' => ['button' => ['background' => ['$type' => 'color', '$value' => TokenValues::color()]]],
        ]);
        [, , $body] = explode("---\n", $md, 3);

        self::assertStringNotContainsString('| Token | Value |', $body);
        self::assertStringNotContainsString('| Property | Value |', $body);
        self::assertStringNotContainsString('`components.button.background`', $body);
        self::assertStringContainsString('## Components', $body);
    }

    public function testSectionsFollowTheSpecificationOrder(): void
    {
        $md = $this->generate([
            'radius' => ['md' => ['$type' => 'dimension', '$value' => ['value' => 8, 'unit' => 'px']]],
            'elevation' => ['sm' => ['$type' => 'shadow', '$value' => [
                'offsetX' => ['value' => 0, 'unit' => 'px'],
                'offsetY' => ['value' => 1, 'unit' => 'px'],
                'blur' => ['value' => 2, 'unit' => 'px'],
                'spread' => ['value' => 0, 'unit' => 'px'],
                'color' => TokenValues::color(),
            ]]],
        ]);

        self::assertLessThan(mb_strpos($md, '## Shapes'), mb_strpos($md, '## Elevation & Depth'));
    }

    private function generate(array $raw): string
    {
        return $this->generator->generate($this->resolver->resolve($raw));
    }
}
