<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Unit\Token;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\DesignTokens\Generator\CssGenerator;
use Symfony\UX\DesignTokens\Resolver\TokenTreeBuilder;
use Symfony\UX\DesignTokens\Token\AbstractToken;
use Symfony\UX\DesignTokens\Token\BorderToken;
use Symfony\UX\DesignTokens\Token\GradientToken;
use Symfony\UX\DesignTokens\Token\ShadowToken;
use Symfony\UX\DesignTokens\Token\TransitionToken;
use Symfony\UX\DesignTokens\Token\TypographyToken;
use Symfony\UX\DesignTokens\TokenTree;

#[CoversClass(AbstractToken::class)]
#[CoversClass(BorderToken::class)]
#[CoversClass(GradientToken::class)]
#[CoversClass(ShadowToken::class)]
#[CoversClass(TransitionToken::class)]
#[CoversClass(TypographyToken::class)]
final class CompositeMemberProjectionTest extends TestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function composites(): iterable
    {
        yield 'transition easing' => ['motion.control', '150ms cubic-bezier(0.2, 0, 0, 1) 0ms'];
        yield 'border stroke' => ['line.dashed', '2px dashed color(srgb 0 0 0)'];
        yield 'typography family and size' => ['type.body', '700 1.5rem/1.4 Ubuntu Sans, system-ui'];
        yield 'shadow colour and offsets' => ['elevation.raised', '0px 2px 4px 0px color(srgb 0 0 0 / 0.2)'];
        yield 'gradient stops' => ['ramp.brand', 'linear-gradient(color(srgb 1 0 0) 0%, color(srgb 0 0 1) 100%)'];
    }

    #[DataProvider('composites')]
    public function testEveryCompositeMemberIsWrittenByItsOwnType(string $path, string $expected): void
    {
        self::assertSame($expected, (string) $this->aliasedComposites()[$path]);
    }

    public function testNoCompositeLeaksTheRawValueOfAnAliasedMember(): void
    {
        $composites = array_column(iterator_to_array(self::composites(), false), 0);
        $rendered = [];
        foreach ($this->aliasedComposites() as $path => $token) {
            if (\in_array($path, $composites, true)) {
                $rendered[$path] = (string) $token;
            }
        }

        self::assertCount(\count($composites), $rendered);

        $leaks = [
            'cubicBezier' => '0.2, 0, 0, 1',
            'strokeStyle' => '4px, 2px',
        ];

        foreach ($rendered as $path => $css) {
            $outsideACall = preg_replace('/[a-z-]+\([^()]*\)/i', '', $css) ?? $css;

            foreach ($leaks as $type => $leak) {
                self::assertStringNotContainsString(
                    $leak,
                    $outsideACall,
                    \sprintf('"%s" wrote an aliased %s member as a bare list: %s', $path, $type, $css),
                );
            }
        }

        self::assertStringContainsString('Ubuntu Sans, system-ui', $rendered['type.body']);
    }

    /** @return array<string, \Symfony\UX\DesignTokens\Token\TokenInterface> */
    private function aliasedComposites(): array
    {
        return TokenTree::flatten(new TokenTreeBuilder()->resolve($this->document()));
    }

    public function testTheCssGeneratorUnrollsComponentsByTheSameRule(): void
    {
        $css = new CssGenerator()->generate(new TokenTreeBuilder()->resolve($this->document()));

        self::assertStringContainsString('--motion-control-timing-function: cubic-bezier(0.2, 0, 0, 1);', $css);
        self::assertStringContainsString('--line-dashed-style: dashed;', $css);
        self::assertStringContainsString('--line-dashed-width: 2px;', $css);
        self::assertStringContainsString('--type-body-font-family: Ubuntu Sans, system-ui;', $css);
        self::assertStringContainsString('--elevation-raised-color: color(srgb 0 0 0 / 0.2);', $css);

        self::assertStringNotContainsString('timing-function: 0.2, 0, 0, 1', $css);
        self::assertStringNotContainsString('style: 4px, 2px', $css);
    }

    public function testEveryProjectionPathGoesThroughOneFunction(): void
    {
        $sources = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(\dirname(__DIR__, 3).'/src')) as $file) {
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $sources[$file->getPathname()] = (string) file_get_contents($file->getPathname());
            }
        }

        $callers = [];
        foreach ($sources as $path => $code) {
            if (str_contains($code, 'CssValue::stringify')) {
                $callers[] = basename($path);
            }
        }
        sort($callers);

        self::assertSame(['ColorToken.php',
            'DimensionToken.php',
            'DurationToken.php',
            'GradientToken.php',
            'TokenFactory.php',
        ], $callers);
    }

    /** @return array<array-key, mixed> */
    private function document(): array
    {
        return json_decode(<<<'JSON'
            {
                "space": { "$type": "dimension", "zero": { "$value": { "value": 0, "unit": "px" } },
                           "sm": { "$value": { "value": 2, "unit": "px" } },
                           "md": { "$value": { "value": 4, "unit": "px" } },
                           "text": { "$value": { "value": 1.5, "unit": "rem" } } },
                "ink": { "$type": "color", "black": { "$value": { "colorSpace": "srgb", "components": [0, 0, 0] } },
                         "shade": { "$value": { "colorSpace": "srgb", "components": [0, 0, 0], "alpha": 0.2 } },
                         "red": { "$value": { "colorSpace": "srgb", "components": [1, 0, 0] } },
                         "blue": { "$value": { "colorSpace": "srgb", "components": [0, 0, 1] } } },
                "stroke": { "$type": "strokeStyle", "dashed": { "$value": {
                    "dashArray": [{ "value": 4, "unit": "px" }, { "value": 2, "unit": "px" }], "lineCap": "round" } } },
                "speed": { "$type": "duration", "quick": { "$value": { "value": 150, "unit": "ms" } },
                           "none": { "$value": { "value": 0, "unit": "ms" } } },
                "curve": { "$type": "cubicBezier", "standard": { "$value": [0.2, 0, 0, 1] } },
                "face": { "$type": "fontFamily", "ui": { "$value": ["Ubuntu Sans", "system-ui"] } },
                "heft": { "$type": "fontWeight", "bold": { "$value": 700 } },
                "ratio": { "$type": "number", "body": { "$value": 1.4 } },

                "motion": { "$type": "transition", "control": { "$value": {
                    "duration": "{speed.quick}", "delay": "{speed.none}", "timingFunction": "{curve.standard}" } } },
                "line": { "$type": "border", "dashed": { "$value": {
                    "color": "{ink.black}", "width": "{space.sm}", "style": "{stroke.dashed}" } } },
                "type": { "$type": "typography", "body": { "$value": {
                    "fontFamily": "{face.ui}", "fontSize": "{space.text}", "fontWeight": "{heft.bold}",
                    "letterSpacing": "{space.sm}", "lineHeight": "{ratio.body}" } } },
                "elevation": { "$type": "shadow", "raised": { "$value": {
                    "color": "{ink.shade}", "offsetX": "{space.zero}", "offsetY": "{space.sm}",
                    "blur": "{space.md}", "spread": "{space.zero}" } } },
                "ramp": { "$type": "gradient", "brand": { "$value": [
                    { "color": "{ink.red}", "position": 0 }, { "color": "{ink.blue}", "position": 1 } ] } }
            }
            JSON, true, 512, \JSON_THROW_ON_ERROR);
    }
}
