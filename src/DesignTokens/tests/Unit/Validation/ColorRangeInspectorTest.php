<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Unit\Validation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\DesignTokens\Resolver\TokenTreeBuilder;
use Symfony\UX\DesignTokens\Token\ColorToken;
use Symfony\UX\DesignTokens\Validation\ColorRangeInspector;

#[CoversClass(ColorRangeInspector::class)]
final class ColorRangeInspectorTest extends TestCase
{
    /** @return iterable<string, array{string, list<int|float>, ?string}> */
    public static function components(): iterable
    {
        yield 'srgb in range' => ['srgb', [0.2, 0.4, 0.8], null];
        yield 'srgb above one' => ['srgb', [1.2, 0.4, 0.8], 'component 0 is 1.2, outside the usual range [0, 1] for "srgb"'];
        yield 'srgb below zero' => ['srgb', [0.2, -0.1, 0.8], 'component 1 is -0.1, outside the usual range [0, 1] for "srgb"'];
        yield 'display-p3 above one' => ['display-p3', [0.2, 0.4, 1.5], 'component 2 is 1.5, outside the usual range [0, 1] for "display-p3"'];
        yield 'xyz-d65 above one' => ['xyz-d65', [0.2, 0.4, 2], 'component 2 is 2, outside the usual range [0, 1] for "xyz-d65"'];

        yield 'hsl hue is never a range finding' => ['hsl', [350, 50, 50], null];
        yield 'hsl saturation above hundred' => ['hsl', [210, 150, 50], 'component 1 is 150, outside the usual range [0, 100] for "hsl"'];
        yield 'hwb whiteness below zero' => ['hwb', [210, -5, 50], 'component 1 is -5, outside the usual range [0, 100] for "hwb"'];

        yield 'lab lightness in range' => ['lab', [55, 20, -30], null];
        yield 'lab lightness above hundred' => ['lab', [140, 20, -30], 'component 0 is 140, outside the usual range [0, 100] for "lab"'];
        yield 'lch lightness above hundred' => ['lch', [140, 40, 230], 'component 0 is 140, outside the usual range [0, 100] for "lch"'];

        yield 'oklch lightness written as a percentage' => ['oklch', [80, 0.18, 255], 'component 0 is 80, outside the usual range [0, 1] for "oklch"'];
        yield 'oklab lightness in range' => ['oklab', [0.62, 0.1, -0.1], null];

        yield 'lch negative chroma' => ['lch', [55, -1, 230], 'component 1 is -1, outside the usual range [0, ∞) for "lch"'];
        yield 'oklch negative chroma' => ['oklch', [0.62, -0.2, 255], 'component 1 is -0.2, outside the usual range [0, ∞) for "oklch"'];
        yield 'oklch positive chroma' => ['oklch', [0.62, 0.2, 255], null];

        yield 'a none component' => ['srgb', [0.2, 0.4, 'none'], null];
    }

    /**
     * @param list<int|float|string> $components
     */
    #[DataProvider('components')]
    public function testReportsOnlyComponentsOutsideTheirUsualRange(string $space, array $components, ?string $expected): void
    {
        $warnings = new ColorRangeInspector()->inspect($this->resolve($space, $components));

        if (null === $expected) {
            self::assertSame([], $warnings);

            return;
        }

        self::assertCount(1, $warnings);
        self::assertSame('color.probe: '.$expected.'.', $warnings[0]);
    }

    public function testReportsEveryOffendingComponentOfAToken(): void
    {
        $warnings = new ColorRangeInspector()->inspect($this->resolve('srgb', [-1, 0.5, 2]));

        self::assertCount(2, $warnings);
        self::assertStringContainsString('component 0 is -1', $warnings[0]);
        self::assertStringContainsString('component 2 is 2', $warnings[1]);
    }

    public function testIgnoresTokensThatAreNotColors(): void
    {
        $tokens = new TokenTreeBuilder()->resolve([
            'dimension' => ['gap' => ['$type' => 'dimension', '$value' => ['value' => 1600, 'unit' => 'px']]],
        ]);

        self::assertSame([], new ColorRangeInspector()->inspect($tokens));
    }

    public function testAColorSpaceWithNoStatedRangeIsNotSecondGuessed(): void
    {
        $tokens = ['color' => ['probe' => new ColorToken([
            'colorSpace' => 'acescc',
            'components' => [42, 42, 42],
        ])]];

        self::assertSame([], new ColorRangeInspector()->inspect($tokens));
    }

    public function testAMalformedColorValueIsSkippedRatherThanReported(): void
    {
        $tokens = ['color' => [
            'no-space' => new ColorToken(['components' => [2, 2, 2]]),
            'no-components' => new ColorToken(['colorSpace' => 'srgb']),
            'not-an-array' => new ColorToken('#ff0000'),
        ]];

        self::assertSame([], new ColorRangeInspector()->inspect($tokens));
    }

    public function testAnEmptyTreeReportsNothing(): void
    {
        self::assertSame([], new ColorRangeInspector()->inspect([]));
    }

    /**
     * @param list<int|float|string> $components
     *
     * @return array<array-key, mixed>
     */
    private function resolve(string $space, array $components): array
    {
        return new TokenTreeBuilder()->resolve([
            'color' => ['probe' => [
                '$type' => 'color',
                '$value' => ['colorSpace' => $space, 'components' => $components],
            ]],
        ]);
    }
}
