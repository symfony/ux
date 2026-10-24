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
use Symfony\UX\DesignTokens\Token\TypographyToken;

#[CoversClass(TypographyToken::class)]
final class TypographyTokenTest extends TestCase
{
    public function testTypeIsTypography(): void
    {
        self::assertSame('typography', new TypographyToken(self::typography(['Inter']))->getType());
    }

    public function testToStringIsTheFontShorthand(): void
    {
        self::assertSame('700 2rem/1.2 Inter, sans-serif', (string) new TypographyToken(self::typography(['Inter', 'sans-serif'], 700, 2, 1.2)));
    }

    /** @param string|list<string> $family */
    #[DataProvider('families')]
    public function testToStringQuotesTheFamilyLikeAFontFamilyToken(string|array $family, string $expected): void
    {
        self::assertStringEndsWith($expected, (string) new TypographyToken(self::typography($family)));
    }

    /** @return iterable<string, array{string|list<string>, string}> */
    public static function families(): iterable
    {
        yield 'one name' => ['Georgia', 'Georgia'];
        yield 'list' => [['Inter', 'sans-serif'], 'Inter, sans-serif'];
        yield 'name needing quotes' => ['Font Awesome 6 Free', '"Font Awesome 6 Free"'];
    }

    /**
     * @param string|list<string> $family
     *
     * @return array<string, mixed>
     */
    private static function typography(string|array $family, int $weight = 400, int|float $size = 1, int|float $lineHeight = 1.5): array
    {
        return [
            'fontFamily' => $family,
            'fontSize' => ['value' => $size, 'unit' => 'rem'],
            'fontWeight' => $weight,
            'letterSpacing' => ['value' => 0, 'unit' => 'px'],
            'lineHeight' => $lineHeight,
        ];
    }
}
