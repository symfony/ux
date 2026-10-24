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
use Symfony\UX\DesignTokens\Token\BorderToken;
use Symfony\UX\DesignTokens\Token\ColorToken;
use Symfony\UX\DesignTokens\Token\CubicBezierToken;
use Symfony\UX\DesignTokens\Token\DimensionToken;
use Symfony\UX\DesignTokens\Token\DurationToken;
use Symfony\UX\DesignTokens\Token\FontFamilyToken;
use Symfony\UX\DesignTokens\Token\FontWeightToken;
use Symfony\UX\DesignTokens\Token\GradientToken;
use Symfony\UX\DesignTokens\Token\NumberToken;
use Symfony\UX\DesignTokens\Token\ShadowToken;
use Symfony\UX\DesignTokens\Token\StrokeStyleToken;
use Symfony\UX\DesignTokens\Token\TokenFactory;
use Symfony\UX\DesignTokens\Token\TransitionToken;
use Symfony\UX\DesignTokens\Token\TypographyToken;

#[CoversClass(TokenFactory::class)]
final class TokenFactoryTest extends TestCase
{
    /** @return iterable<string, array{string, mixed, class-string}> */
    public static function types(): iterable
    {
        $color = ['colorSpace' => 'srgb', 'components' => [0.2, 0.4, 0.8]];
        $dimension = ['value' => 1, 'unit' => 'rem'];

        yield 'color' => ['color', $color, ColorToken::class];
        yield 'dimension' => ['dimension', $dimension, DimensionToken::class];
        yield 'number' => ['number', 1.5, NumberToken::class];
        yield 'duration' => ['duration', ['value' => 100, 'unit' => 'ms'], DurationToken::class];
        yield 'fontWeight' => ['fontWeight', 700, FontWeightToken::class];
        yield 'fontFamily' => ['fontFamily', ['Inter', 'sans-serif'], FontFamilyToken::class];
        yield 'cubicBezier' => ['cubicBezier', [0.0, 0.0, 1.0, 1.0], CubicBezierToken::class];
        yield 'strokeStyle' => ['strokeStyle', 'solid', StrokeStyleToken::class];
        yield 'gradient' => ['gradient', [['color' => $color, 'position' => 0]], GradientToken::class];
        yield 'typography' => ['typography', ['fontFamily' => ['Inter'], 'fontSize' => $dimension, 'fontWeight' => 400, 'letterSpacing' => $dimension, 'lineHeight' => 1.5], TypographyToken::class];
        yield 'border' => ['border', ['color' => $color, 'width' => $dimension, 'style' => 'solid'], BorderToken::class];
        yield 'shadow' => ['shadow', ['color' => $color, 'offsetX' => $dimension, 'offsetY' => $dimension, 'blur' => $dimension, 'spread' => $dimension], ShadowToken::class];
        yield 'transition' => ['transition', ['duration' => ['value' => 100, 'unit' => 'ms'], 'delay' => ['value' => 0, 'unit' => 'ms'], 'timingFunction' => [0.0, 0.0, 1.0, 1.0]], TransitionToken::class];
    }

    /** @param class-string $expected */
    #[DataProvider('types')]
    public function testCreatesOneValueObjectPerDtcgType(string $type, mixed $value, string $expected): void
    {
        $token = TokenFactory::create($type, $value);

        self::assertInstanceOf($expected, $token);
        self::assertSame($type, $token->getType());
    }

    public function testKeepsDescriptionExtensionsAndDeprecation(): void
    {
        $token = TokenFactory::create('number', 1, 'A number', ['com.acme' => true], 'use two');

        self::assertSame('A number', $token->getDescription());
        self::assertSame(['com.acme' => true], $token->getExtensions());
        self::assertTrue($token->isDeprecated());
        self::assertSame('use two', $token->getDeprecationMessage());
    }

    #[DataProvider('invalidTokens')]
    public function testRejectsAValueItCannotBuild(string $type, mixed $value, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        TokenFactory::create($type, $value);
    }

    /** @return iterable<string, array{string, mixed, string}> */
    public static function invalidTokens(): iterable
    {
        yield 'unknown type' => ['nope', 'x', 'Unknown DTCG token type "nope"'];
        yield 'gradient that is not a list of stops' => ['gradient', ['stop' => []], 'must be a list of color stops'];
        yield 'gradient stop that is not an object' => ['gradient', ['red'], 'color stop must be an object'];
    }
}
