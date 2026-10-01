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
use Symfony\UX\DesignTokens\Token\DurationToken;

#[CoversClass(DurationToken::class)]
final class DurationTokenTest extends TestCase
{
    public function testTypeIsDuration(): void
    {
        self::assertSame('duration', new DurationToken(['value' => 0, 'unit' => 'ms'])->getType());
    }

    /** @param array{value: int|float, unit: string} $value */
    #[DataProvider('durations')]
    public function testToStringIsACssTime(array $value, string $expected): void
    {
        self::assertSame($expected, (string) new DurationToken($value));
    }

    /** @return iterable<string, array{array{value: int|float, unit: string}, string}> */
    public static function durations(): iterable
    {
        yield 'milliseconds' => [['value' => 300, 'unit' => 'ms'], '300ms'];
        yield 'seconds' => [['value' => 0.5, 'unit' => 's'], '0.5s'];
        yield 'zero' => [['value' => 0, 'unit' => 'ms'], '0ms'];
    }
}
