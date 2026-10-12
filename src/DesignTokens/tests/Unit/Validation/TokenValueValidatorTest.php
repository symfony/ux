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
use Symfony\UX\DesignTokens\Exception\InvalidArgumentException;
use Symfony\UX\DesignTokens\Tests\Fixtures\TokenValues;
use Symfony\UX\DesignTokens\Token\ColorToken;
use Symfony\UX\DesignTokens\Validation\ColorRangeInspector;
use Symfony\UX\DesignTokens\Validation\TokenValueValidator;

#[CoversClass(TokenValueValidator::class)]
final class TokenValueValidatorTest extends TestCase
{
    #[DataProvider('validValueProvider')]
    public function testAcceptsDtcgValues(string $type, mixed $value): void
    {
        new TokenValueValidator()->validate($type, $value);
        self::assertTrue(true);
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function validValueProvider(): iterable
    {
        yield 'srgb with fallback' => ['color', ['colorSpace' => 'srgb', 'components' => [0.2, 'none', 0.8], 'alpha' => 0.5, 'hex' => '#3366cc']];
        yield 'lab unbounded axes' => ['color', ['colorSpace' => 'lab', 'components' => [50, -160, 170]]];
        yield 'oklab normalized lightness' => ['color', ['colorSpace' => 'oklab', 'components' => [0.5, -0.4, 0.4]]];
        yield 'oklch normalized lightness' => ['color', ['colorSpace' => 'oklch', 'components' => [0.75, 0.4, 359.9]]];
        yield 'dimension' => ['dimension', ['value' => -16.5, 'unit' => 'px']];
        yield 'duration' => ['duration', ['value' => 250, 'unit' => 'ms']];
        yield 'number' => ['number', -1.5];
        yield 'font family' => ['fontFamily', ['Inter', 'sans-serif']];
        yield 'font weight' => ['fontWeight', 'semi-bold'];
        yield 'cubic bezier' => ['cubicBezier', [0, -2, 1, 3]];
        yield 'stroke style keyword' => ['strokeStyle', 'dashed'];
        yield 'stroke style object' => ['strokeStyle', ['dashArray' => [TokenValues::dimension(2), TokenValues::dimension(4)], 'lineCap' => 'round']];
        yield 'border' => ['border', TokenValues::border()];
        yield 'transition' => ['transition', TokenValues::transition()];
        yield 'single shadow' => ['shadow', TokenValues::shadow(true)];
        yield 'shadow array' => ['shadow', [TokenValues::shadow(true), TokenValues::shadow(false)]];
        yield 'gradient' => ['gradient', [['color' => TokenValues::color(), 'position' => 0], ['color' => TokenValues::color(), 'position' => 1]]];
        yield 'gradient positions are clamped at consumption' => ['gradient', [['color' => TokenValues::color(), 'position' => -99], ['color' => TokenValues::color(), 'position' => 42]]];
        yield 'typography' => ['typography', ['fontFamily' => 'Inter', 'fontSize' => TokenValues::dimension(16), 'fontWeight' => 400, 'letterSpacing' => TokenValues::dimension(0), 'lineHeight' => 1.5]];
    }

    #[DataProvider('invalidValueProvider')]
    public function testRejectsInvalidDtcgValues(string $type, mixed $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TokenValueValidator()->validate($type, $value);
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function invalidValueProvider(): iterable
    {
        yield 'legacy string color' => ['color', '#3366ff'];
        yield 'unknown color space' => ['color', ['colorSpace' => 'rgb', 'components' => [0, 0, 0]]];
        yield 'hue 360' => ['color', ['colorSpace' => 'hsl', 'components' => [360, 50, 50]]];
        yield 'lch hue 360' => ['color', ['colorSpace' => 'lch', 'components' => [50, 20, 360]]];
        yield 'hex form' => ['color', ['colorSpace' => 'srgb', 'components' => [0, 0, 0], 'hex' => '#000']];
        yield 'color unknown property' => ['color', ['colorSpace' => 'srgb', 'components' => [0, 0, 0], 'fallback' => '#000000']];
        yield 'legacy dimension' => ['dimension', '16px'];
        yield 'dimension value type' => ['dimension', ['value' => '16', 'unit' => 'px']];
        yield 'dimension unknown property' => ['dimension', ['value' => 16, 'unit' => 'px', 'extra' => true]];
        yield 'non-finite number' => ['number', \INF];
        yield 'empty font family' => ['fontFamily', []];
        yield 'curly family item' => ['fontFamily', ['{font.base}']];
        yield 'font weight keyword' => ['fontWeight', 'semibold'];
        yield 'bezier x range' => ['cubicBezier', [-0.1, 0, 1, 1]];
        yield 'empty stroke dashes' => ['strokeStyle', ['dashArray' => [], 'lineCap' => 'round']];
        yield 'stroke style shape' => ['strokeStyle', []];
        yield 'stroke line cap' => ['strokeStyle', ['dashArray' => [TokenValues::dimension(2)], 'lineCap' => 'flat']];
        yield 'border missing style' => ['border', ['color' => TokenValues::color(), 'width' => TokenValues::dimension(1)]];
        yield 'border shape' => ['border', []];
        yield 'transition unknown property' => ['transition', ['duration' => ['value' => 1, 'unit' => 's'], 'delay' => ['value' => 0, 'unit' => 's'], 'timingFunction' => [0, 0, 1, 1], 'property' => 'all']];
        yield 'transition shape' => ['transition', []];
        yield 'empty shadows' => ['shadow', []];
        yield 'shadow item shape' => ['shadow', ['not-a-shadow']];
        yield 'shadow inset type' => ['shadow', [...TokenValues::shadow(false), 'inset' => 1]];
        yield 'empty gradient' => ['gradient', []];
        yield 'gradient stop shape' => ['gradient', ['not-a-stop']];
        yield 'gradient position must be finite' => ['gradient', [['color' => TokenValues::color(), 'position' => \INF]]];
        yield 'typography missing letter spacing' => ['typography', ['fontFamily' => 'Inter', 'fontSize' => TokenValues::dimension(16), 'fontWeight' => 400, 'lineHeight' => 1.5]];
        yield 'typography shape' => ['typography', []];
        yield 'typography string line height' => ['typography', ['fontFamily' => 'Inter', 'fontSize' => TokenValues::dimension(16), 'fontWeight' => 400, 'letterSpacing' => TokenValues::dimension(0), 'lineHeight' => '1.5']];
        yield 'string is not DTCG type' => ['string', 'legacy'];
        yield 'malformed reference object' => ['dimension', ['$ref' => 'tokens.json']];
    }

    /** @param list<int|float> $components */
    #[DataProvider('outOfRangeProvider')]
    public function testReportsOutOfRangeComponentsWithoutRejectingThem(string $space, array $components): void
    {
        $value = ['colorSpace' => $space, 'components' => $components];

        new TokenValueValidator()->validate('color', $value, '/fixture');

        self::assertCount(1, new ColorRangeInspector()->inspect(['brand' => new ColorToken($value)]));
    }

    /** @return iterable<string, array{string, list<int|float>}> */
    public static function outOfRangeProvider(): iterable
    {
        yield 'srgb above' => ['srgb', [1.1, 0, 0]];
        yield 'srgb below' => ['srgb', [-0.1, 0, 0]];
        yield 'oklab lightness' => ['oklab', [50, 0, 0]];
        yield 'D65 white point' => ['xyz-d65', [0.9505, 1, 1.089]];
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function referenceShapedValueProvider(): iterable
    {
        yield 'color space object' => ['color', ['colorSpace' => ['$ref' => '#/x'], 'components' => 'garbage', 'alpha' => 9]];
        yield 'components object' => ['color', ['colorSpace' => 'srgb', 'components' => ['$ref' => '#/x']]];
        yield 'whole curly value' => ['color', '{palette.blue}'];
        yield 'whole pointer value' => ['dimension', ['$ref' => '#/space/base/$value']];
        yield 'cubic bezier coordinate' => ['cubicBezier', [['$ref' => '#/x'], 0, 1, 1]];
        yield 'shadow layer' => ['shadow', ['{shadow.base}']];
    }

    #[DataProvider('referenceShapedValueProvider')]
    public function testAReferenceShapedValueIsNotAValue(string $type, mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        new TokenValueValidator()->validate($type, $value);
    }
}
