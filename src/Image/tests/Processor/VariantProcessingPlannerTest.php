<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Processor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Exception\ImageLimitExceededException;
use Symfony\UX\Image\InspectedImage;
use Symfony\UX\Image\ProcessingLimits;
use Symfony\UX\Image\Processor\PlannedVariant;
use Symfony\UX\Image\Processor\VariantProcessingPlan;
use Symfony\UX\Image\Processor\VariantProcessingPlanner;
use Symfony\UX\Image\Transformation\ResizeGeometryCalculator;

#[CoversClass(VariantProcessingPlanner::class)]
#[CoversClass(VariantProcessingPlan::class)]
#[CoversClass(PlannedVariant::class)]
final class VariantProcessingPlannerTest extends TestCase
{
    public function testPlansWidthAndHeightOnlyVariantsAndEveryArtifact()
    {
        $plan = $this->planner()->plan(
            new InspectedImage('jpeg', 'image/jpeg', 400, 200, 1_000),
            [
                'by_width' => ['width' => 100],
                'by_height' => ['height' => 50],
            ],
            ['webp', 'jpg', 'jpeg'],
        );

        self::assertSame(['webp', 'jpeg'], $plan->formats);
        self::assertSame(4, $plan->artifactCount);
        self::assertSame(20_000, $plan->outputPixels);
        self::assertSame(100, $plan->variants[0]->geometry->canvasWidth);
        self::assertSame(50, $plan->variants[0]->geometry->canvasHeight);
        self::assertSame(100, $plan->variants[1]->geometry->canvasWidth);
        self::assertSame(50, $plan->variants[1]->geometry->canvasHeight);
    }

    public function testRejectsCumulativeArtifactBudgetBeforeProcessing()
    {
        $this->expectException(ImageLimitExceededException::class);
        $this->expectExceptionMessage('Generated image pixel count 10000 exceeds the configured output limit of 9999.');

        $this->planner(maxOutputPixels: 9_999)->plan(
            new InspectedImage('jpeg', 'image/jpeg', 400, 200, 1_000),
            ['responsive' => ['width' => 100]],
            ['webp', 'jpeg'],
        );
    }

    public function testRejectsVariantCountBeforeProcessing()
    {
        $this->expectException(ImageLimitExceededException::class);
        $this->expectExceptionMessage('variant count');

        $this->planner(maxVariants: 1)->plan(
            new InspectedImage('jpeg', 'image/jpeg', 400, 200, 1_000),
            [
                'small' => ['width' => 100],
                'large' => ['width' => 200],
            ],
            ['jpeg'],
        );
    }

    public function testSkipsMalformedFormatsAndVariants()
    {
        $plan = $this->planner()->plan(
            new InspectedImage('jpeg', 'image/jpeg', 400, 200, 1_000),
            ['invalid' => 'not-an-array'],
            [42, 'jpeg'],
        );

        self::assertSame(['jpeg'], $plan->formats);
        self::assertSame([], $plan->variants);
    }

    public function testWrapsInvalidTransformationConfiguration()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('plan variants');

        $this->planner()->plan(
            new InspectedImage('jpeg', 'image/jpeg', 400, 200, 1_000),
            ['invalid' => ['width' => 100, 'mode' => 'stretch']],
            ['jpeg'],
        );
    }

    private function planner(int $maxVariants = 12, int $maxOutputPixels = 80_000_000): VariantProcessingPlanner
    {
        return new VariantProcessingPlanner(
            new ProcessingLimits(maxVariants: $maxVariants, maxOutputPixels: $maxOutputPixels),
            new ResizeGeometryCalculator(),
        );
    }
}
