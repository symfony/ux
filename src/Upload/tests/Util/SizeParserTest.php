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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Upload\Util\SizeParser;

final class SizeParserTest extends TestCase
{
    #[Test]
    public function integerPassthrough(): void
    {
        $this->assertSame(0, SizeParser::parse(0));
        $this->assertSame(1, SizeParser::parse(1));
        $this->assertSame(5242880, SizeParser::parse(5242880));
    }

    #[Test]
    public function kilobytes(): void
    {
        $this->assertSame(1024, SizeParser::parse('1K'));
        $this->assertSame(5120, SizeParser::parse('5K'));
    }

    #[Test]
    public function megabytes(): void
    {
        $this->assertSame(5242880, SizeParser::parse('5M'));
        $this->assertSame(104857600, SizeParser::parse('100M'));
    }

    #[Test]
    public function gigabytes(): void
    {
        $this->assertSame(2147483648, SizeParser::parse('2G'));
    }

    #[Test]
    public function terabytes(): void
    {
        $this->assertSame(1099511627776, SizeParser::parse('1T'));
    }

    #[Test]
    public function caseInsensitive(): void
    {
        $this->assertSame(1024, SizeParser::parse('1k'));
        $this->assertSame(5242880, SizeParser::parse('5m'));
        $this->assertSame(1073741824, SizeParser::parse('1g'));
        $this->assertSame(1099511627776, SizeParser::parse('1t'));
    }

    #[Test]
    public function noUnitTreatedAsBytes(): void
    {
        $this->assertSame(1024, SizeParser::parse('1024'));
        $this->assertSame(0, SizeParser::parse('0'));
    }

    #[Test]
    public function emptyStringThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SizeParser::parse('');
    }

    #[Test]
    public function lettersOnlyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SizeParser::parse('abc');
    }

    #[Test]
    public function invalidUnitThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SizeParser::parse('5X');
    }

    #[Test]
    public function negativeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SizeParser::parse('-5M');
    }

    #[Test]
    public function decimalThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SizeParser::parse('5.5M');
    }
}
