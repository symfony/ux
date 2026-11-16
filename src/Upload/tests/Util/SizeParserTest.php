<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Upload\Util\SizeParser;

#[CoversClass(SizeParser::class)]
final class SizeParserTest extends TestCase
{
    /**
     * @return iterable<string, array{int|string, int}>
     */
    public static function sizes(): iterable
    {
        yield 'integer zero' => [0, 0];
        yield 'integer bytes' => [5242880, 5242880];
        yield 'string without unit' => ['1024', 1024];
        yield 'string zero' => ['0', 0];
        yield 'kilobytes' => ['5K', 5120];
        yield 'megabytes' => ['100M', 104857600];
        yield 'gigabytes' => ['2G', 2147483648];
        yield 'terabytes' => ['1T', 1099511627776];
        yield 'lowercase kilobytes' => ['1k', 1024];
        yield 'lowercase megabytes' => ['5m', 5242880];
        yield 'lowercase gigabytes' => ['1g', 1073741824];
        yield 'lowercase terabytes' => ['1t', 1099511627776];
    }

    #[DataProvider('sizes')]
    public function testParsesASize(int|string $size, int $bytes): void
    {
        self::assertSame($bytes, SizeParser::parse($size));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidSizes(): iterable
    {
        yield 'empty' => [''];
        yield 'letters only' => ['abc'];
        yield 'unknown unit' => ['5X'];
        yield 'negative' => ['-5M'];
        yield 'decimal' => ['5.5M'];
    }

    #[DataProvider('invalidSizes')]
    public function testRejectsAnInvalidSize(string $size): void
    {
        $this->expectException(\InvalidArgumentException::class);

        SizeParser::parse($size);
    }
}
