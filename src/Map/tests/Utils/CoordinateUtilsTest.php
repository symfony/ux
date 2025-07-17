<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Utils;

use PHPUnit\Framework\TestCase;

class CoordinateUtilsTest extends TestCase
{
    public function testDecimalToDMSConvertsCorrectly(): void
    {
        $result = CoordinateUtils::decimalToDMS(48.8588443);
        $this->assertSame([48, 51, 31.83948], $result);
    }

    public function testDecimalToDMSHandlesNegativeValues(): void
    {
        $result = CoordinateUtils::decimalToDMS(-48.8588443);
        $this->assertSame([-48, 51, 31.83948], $result);
    }

    public function testDMSToDecimalConvertsCorrectly(): void
    {
        $result = CoordinateUtils::DMSToDecimal(48, 51, 31.8388);
        $this->assertSame(48.858844, $result);
    }

    public function testDMSToDecimalHandlesNegativeValues(): void
    {
        $result = CoordinateUtils::DMSToDecimal(-48, 51, 31.8388);
        $this->assertSame(-48.858844, $result);
    }

    public function testDMSToDecimalHandlesZeroValues(): void
    {
        $result = CoordinateUtils::DMSToDecimal(0, 0, 0.0);
        $this->assertSame(0.0, $result);
    }
}
