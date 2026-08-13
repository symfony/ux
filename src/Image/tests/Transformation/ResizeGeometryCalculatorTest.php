<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Transformation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Transformation\FocalPoint;
use Symfony\UX\Image\Transformation\ResizeGeometry;
use Symfony\UX\Image\Transformation\ResizeGeometryCalculator;
use Symfony\UX\Image\Transformation\ResizeMode;

#[CoversClass(ResizeGeometryCalculator::class)]
#[CoversClass(ResizeGeometry::class)]
#[CoversClass(FocalPoint::class)]
#[CoversClass(ResizeMode::class)]
final class ResizeGeometryCalculatorTest extends TestCase
{
    private ResizeGeometryCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new ResizeGeometryCalculator();
    }

    public function testFitPreservesRatioAndDoesNotUpscale(): void
    {
        $geometry = $this->calculator->calculate(400, 200, 100, 100, ResizeMode::Fit);

        self::assertSame(100, $geometry->canvasWidth);
        self::assertSame(50, $geometry->canvasHeight);
        self::assertSame(100, $geometry->destinationWidth);
        self::assertSame(50, $geometry->destinationHeight);

        $noUpscale = $this->calculator->calculate(40, 20, 100, 100, ResizeMode::Fit);
        self::assertSame(40, $noUpscale->canvasWidth);
        self::assertSame(20, $noUpscale->canvasHeight);
    }

    public function testFillUsesTargetCanvasWithoutDistortion(): void
    {
        $geometry = $this->calculator->calculate(400, 200, 100, 100, ResizeMode::Fill);

        self::assertSame(100, $geometry->canvasWidth);
        self::assertSame(100, $geometry->canvasHeight);
        self::assertSame(100, $geometry->destinationWidth);
        self::assertSame(50, $geometry->destinationHeight);
        self::assertSame(25, $geometry->destinationY);
    }

    public function testCropUsesFocalPoint(): void
    {
        $left = $this->calculator->calculate(400, 200, 100, 100, ResizeMode::Crop, new FocalPoint(0, 0.5));
        $right = $this->calculator->calculate(400, 200, 100, 100, ResizeMode::Crop, new FocalPoint(1, 0.5));

        self::assertSame(0, $left->sourceX);
        self::assertSame(200, $right->sourceX);
        self::assertSame(200, $right->sourceWidth);
    }

    public function testCropPreservesRequestedRatioWithoutUpscaling(): void
    {
        $geometry = $this->calculator->calculate(100, 50, 300, 300, ResizeMode::Crop);

        self::assertSame(50, $geometry->canvasWidth);
        self::assertSame(50, $geometry->canvasHeight);
        self::assertSame(50, $geometry->sourceWidth);
        self::assertSame(50, $geometry->sourceHeight);
    }

    public function testPercentageFocalPoint(): void
    {
        $point = FocalPoint::fromString('50% 30%');

        self::assertSame(0.5, $point->x);
        self::assertSame(0.3, $point->y);
    }

    public function testNamedFocalPoints(): void
    {
        self::assertSame(0.0, FocalPoint::fromString('top')->y);
        self::assertSame(1.0, FocalPoint::fromString('bottom')->y);
        self::assertSame(0.0, FocalPoint::fromString('left')->x);
        self::assertSame(1.0, FocalPoint::fromString('right')->x);
        self::assertSame(0.5, FocalPoint::fromString('center')->x);
    }

    public function testRejectsInvalidFocalPoint(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        FocalPoint::fromString('outside');
    }

    public function testRejectsOutOfRangeFocalPoint(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new FocalPoint(1.1, 0.5);
    }

    public function testCalculatesMissingTargetDimension(): void
    {
        $fromHeight = $this->calculator->calculate(400, 200, 0, 50, ResizeMode::Fit);
        $fromWidth = $this->calculator->calculate(400, 200, 100, 0, ResizeMode::Fit);

        self::assertSame(100, $fromHeight->canvasWidth);
        self::assertSame(50, $fromHeight->canvasHeight);
        self::assertSame(100, $fromWidth->canvasWidth);
        self::assertSame(50, $fromWidth->canvasHeight);
    }

    public function testCropPortraitSource(): void
    {
        $geometry = $this->calculator->calculate(200, 400, 100, 100, ResizeMode::Crop);

        self::assertSame(200, $geometry->sourceHeight);
    }

    public function testRejectsInvalidDimensions(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calculator->calculate(0, 200, 100, 100, ResizeMode::Fit);
    }
}
