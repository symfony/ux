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
use Symfony\UX\DesignTokens\Token\CubicBezierToken;

#[CoversClass(CubicBezierToken::class)]
final class CubicBezierTokenTest extends TestCase
{
    public function testTypeIsCubicBezier(): void
    {
        self::assertSame('cubicBezier', new CubicBezierToken([0, 0, 1, 1])->getType());
    }

    /** @param list<float> $bezier */
    #[DataProvider('bezierProvider')]
    public function testToStringFormatsCubicBezier(array $bezier, string $expected): void
    {
        self::assertSame($expected, (string) new CubicBezierToken($bezier));
    }

    /** @return iterable<string, array{list<float>, string}> */
    public static function bezierProvider(): iterable
    {
        yield 'linear' => [[0, 0, 1, 1],       'cubic-bezier(0, 0, 1, 1)'];
        yield 'ease' => [[0.25, 0.1, 0.25, 1], 'cubic-bezier(0.25, 0.1, 0.25, 1)'];
        yield 'ease-in' => [[0.42, 0, 1, 1],     'cubic-bezier(0.42, 0, 1, 1)'];
        yield 'ease-out' => [[0, 0, 0.58, 1],     'cubic-bezier(0, 0, 0.58, 1)'];
        yield 'spring' => [[0.5, -0.5, 0.5, 1.5], 'cubic-bezier(0.5, -0.5, 0.5, 1.5)'];
        yield 'tiny control point' => [[0.00001, 0, 0.5, 1], 'cubic-bezier(0.00001, 0, 0.5, 1)'];
    }
}
