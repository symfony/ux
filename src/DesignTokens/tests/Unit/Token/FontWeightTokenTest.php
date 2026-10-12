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
use Symfony\UX\DesignTokens\Token\FontWeightToken;
use Symfony\UX\DesignTokens\Validation\TokenValueValidator;

#[CoversClass(FontWeightToken::class)]
final class FontWeightTokenTest extends TestCase
{
    public function testTypeIsFontWeight(): void
    {
        self::assertSame('fontWeight', new FontWeightToken(400)->getType());
    }

    #[DataProvider('fontWeightProvider')]
    public function testToStringReturnsValue(int|string $value, string $expected): void
    {
        self::assertSame($expected, (string) new FontWeightToken($value));
    }

    /** @return iterable<string, array{int|string, string}> */
    public static function fontWeightProvider(): iterable
    {
        yield 'thin 100' => [100, '100'];
        yield 'regular 400' => [400, '400'];
        yield 'bold 700' => [700, '700'];
        yield 'black 900' => [900, '900'];
        yield 'keyword thin' => ['thin', '100'];
        yield 'keyword hairline' => ['hairline', '100'];
        yield 'keyword extra-light' => ['extra-light', '200'];
        yield 'keyword light' => ['light', '300'];
        yield 'keyword normal' => ['normal', '400'];
        yield 'keyword regular' => ['regular', '400'];
        yield 'keyword book' => ['book', '400'];
        yield 'keyword medium' => ['medium', '500'];
        yield 'keyword semi-bold' => ['semi-bold', '600'];
        yield 'keyword demi-bold' => ['demi-bold', '600'];
        yield 'keyword bold' => ['bold', '700'];
        yield 'keyword extra-bold' => ['extra-bold', '800'];
        yield 'keyword ultra-bold' => ['ultra-bold', '800'];
        yield 'keyword black' => ['black', '900'];
        yield 'keyword heavy' => ['heavy', '900'];
        yield 'keyword extra-black' => ['extra-black', '950'];
        yield 'keyword ultra-black' => ['ultra-black', '950'];
        yield 'mixed case keyword' => ['Semi-Bold', '600'];
        yield 'an unknown keyword is left alone' => ['fantasy', 'fantasy'];
    }

    public function testTheAuthoredKeywordSurvivesInTheValue(): void
    {
        $token = new FontWeightToken('extra-bold');

        self::assertSame('extra-bold', $token->getValue());
        self::assertSame('800', (string) $token);
    }

    public function testEveryKeywordTheValidatorAcceptsHasAWeight(): void
    {
        $accepted = new \ReflectionClass(TokenValueValidator::class)->getConstant('FONT_WEIGHTS');
        self::assertIsArray($accepted);

        foreach ($accepted as $keyword) {
            self::assertIsString($keyword);
            self::assertMatchesRegularExpression(
                '/^\d{3}$/',
                (string) new FontWeightToken($keyword),
                \sprintf('The validator accepts "%s" but it has no numeric weight.', $keyword),
            );
        }
    }
}
