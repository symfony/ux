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
use Symfony\UX\DesignTokens\Token\NumberToken;

#[CoversClass(NumberToken::class)]
final class NumberTokenTest extends TestCase
{
    public function testTypeIsNumber(): void
    {
        self::assertSame('number', new NumberToken(0)->getType());
    }

    #[DataProvider('numberProvider')]
    public function testToStringCastsToString(int|float $value, string $expected): void
    {
        self::assertSame($expected, (string) new NumberToken($value));
    }

    /** @return iterable<string, array{int|float, string}> */
    public static function numberProvider(): iterable
    {
        yield 'zero int' => [0, '0'];
        yield 'positive' => [42, '42'];
        yield 'negative' => [-1, '-1'];
        yield 'float' => [1.618, '1.618'];
        yield 'float zero' => [0.0, '0'];
        yield 'small float' => [0.0000001, '0.0000001'];
        yield 'small negative float' => [-0.00012345, '-0.00012345'];
        yield 'large float' => [1.5e20, '150000000000000000000'];
    }
}
