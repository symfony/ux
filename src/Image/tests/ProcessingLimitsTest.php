<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Exception\ImageLimitExceededException;
use Symfony\UX\Image\ProcessingLimits;

#[CoversClass(ProcessingLimits::class)]
#[CoversClass(ImageLimitExceededException::class)]
final class ProcessingLimitsTest extends TestCase
{
    public function testRejectsNonPositiveLimit()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be positive');

        new ProcessingLimits(maxVariants: 0);
    }

    public function testAcceptsInputWithinBudget()
    {
        new ProcessingLimits(maxInputBytes: 100, maxWidth: 10, maxHeight: 10, maxPixels: 100)->assertInput(100, 10, 10);

        self::addToAssertionCount(1);
    }

    public function testRejectsExcessiveBytes()
    {
        $this->expectException(ImageLimitExceededException::class);
        $this->expectExceptionMessage('101 bytes');

        new ProcessingLimits(maxInputBytes: 100)->assertInput(101, 1, 1);
    }

    public function testRejectsExcessiveDimensions()
    {
        $this->expectException(ImageLimitExceededException::class);
        $this->expectExceptionMessage('11x5');

        new ProcessingLimits(maxWidth: 10, maxHeight: 10)->assertInput(1, 11, 5);
    }

    public function testRejectsExcessivePixels()
    {
        $this->expectException(ImageLimitExceededException::class);
        $this->expectExceptionMessage('121');

        new ProcessingLimits(maxWidth: 20, maxHeight: 20, maxPixels: 100)->assertInput(1, 11, 11);
    }

    public function testRejectsOutputDimensionsBeforeAllocation()
    {
        $this->expectException(ImageLimitExceededException::class);
        $this->expectExceptionMessage('11x5');

        new ProcessingLimits(maxWidth: 10, maxHeight: 10)->assertOutputAllocation(11, 5);
    }

    public function testRejectsOutputPixelsBeforeAllocationWithoutOverflow()
    {
        $this->expectException(ImageLimitExceededException::class);
        $this->expectExceptionMessage('output limit');

        new ProcessingLimits(maxWidth: \PHP_INT_MAX, maxHeight: \PHP_INT_MAX, maxOutputPixels: 100)
            ->assertOutputAllocation(\PHP_INT_MAX, 2);
    }

    public function testReportsActualOutputPixels()
    {
        $this->expectException(ImageLimitExceededException::class);
        $this->expectExceptionMessage('110');

        new ProcessingLimits(maxWidth: 20, maxHeight: 20, maxOutputPixels: 100)
            ->assertOutputAllocation(11, 10);
    }
}
