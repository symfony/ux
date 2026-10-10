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
use Symfony\UX\DesignTokens\Token\DimensionToken;

#[CoversClass(DimensionToken::class)]
final class DimensionTokenTest extends TestCase
{
    public function testTypeIsDimension(): void
    {
        self::assertSame('dimension', new DimensionToken(['value' => 16, 'unit' => 'px'])->getType());
    }

    /** @param array{value: int|float, unit: string} $value */
    #[DataProvider('dimensions')]
    public function testToStringIsACssLength(array $value, string $expected): void
    {
        self::assertSame($expected, (string) new DimensionToken($value));
    }

    /** @return iterable<string, array{array{value: int|float, unit: string}, string}> */
    public static function dimensions(): iterable
    {
        yield 'px' => [['value' => 16, 'unit' => 'px'], '16px'];
        yield 'rem' => [['value' => 1.5, 'unit' => 'rem'], '1.5rem'];
        yield 'negative' => [['value' => -0.02, 'unit' => 'rem'], '-0.02rem'];
        yield 'zero' => [['value' => 0, 'unit' => 'px'], '0px'];
        yield 'tiny' => [['value' => 0.00001, 'unit' => 'rem'], '0.00001rem'];
    }
}
