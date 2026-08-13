<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Processor;

use Symfony\UX\Image\Exception\ImageLimitExceededException;
use Symfony\UX\Image\Exception\ImageProcessingException;
use Symfony\UX\Image\InspectedImage;
use Symfony\UX\Image\ProcessingLimits;
use Symfony\UX\Image\Transformation\FocalPoint;
use Symfony\UX\Image\Transformation\ResizeGeometryCalculator;
use Symfony\UX\Image\Transformation\ResizeMode;

final readonly class VariantProcessingPlanner
{
    public function __construct(
        private ProcessingLimits $limits,
        private ResizeGeometryCalculator $geometryCalculator,
    ) {
    }

    /**
     * @param array<string, mixed>    $variantConfigs
     * @param array<array-key, mixed> $configuredFormats
     */
    public function plan(InspectedImage $input, array $variantConfigs, array $configuredFormats): VariantProcessingPlan
    {
        if (\count($variantConfigs) > $this->limits->maxVariants) {
            throw ImageLimitExceededException::variants(\count($variantConfigs), $this->limits->maxVariants);
        }

        $formats = [];
        foreach ($configuredFormats as $format) {
            if (!\is_string($format)) {
                continue;
            }
            $format = 'jpg' === $format ? 'jpeg' : $format;
            if (!\in_array($format, $formats, true)) {
                $formats[] = $format;
            }
        }

        $variants = [];
        $outputPixels = 0;
        foreach ($variantConfigs as $variantName => $config) {
            if (!\is_array($config)) {
                continue;
            }

            $width = \is_int($config['width'] ?? null) ? $config['width'] : 0;
            $height = \is_int($config['height'] ?? null) ? $config['height'] : 0;
            $mode = \is_string($config['mode'] ?? null) ? $config['mode'] : 'fit';
            $position = \is_string($config['position'] ?? null) ? $config['position'] : 'center';
            try {
                $geometry = $this->geometryCalculator->calculate(
                    $input->width,
                    $input->height,
                    $width,
                    $height,
                    ResizeMode::from($mode),
                    FocalPoint::fromString($position),
                );
            } catch (\ValueError|\InvalidArgumentException $e) {
                throw ImageProcessingException::processingFailed('plan variants', $e->getMessage());
            }
            $this->limits->assertOutputAllocation($geometry->canvasWidth, $geometry->canvasHeight);

            $artifactPixels = $geometry->canvasWidth * $geometry->canvasHeight;
            $remaining = $this->limits->maxOutputPixels - $outputPixels;
            if ([] !== $formats && $artifactPixels > intdiv($remaining, \count($formats))) {
                throw ImageLimitExceededException::outputPixels($this->limits->maxOutputPixels + 1, $this->limits->maxOutputPixels);
            }
            $outputPixels += $artifactPixels * \count($formats);
            $variants[] = new PlannedVariant(
                name: (string) $variantName,
                width: $width,
                height: $height,
                mode: $mode,
                position: $position,
                quality: \is_int($config['quality'] ?? null) ? $config['quality'] : 80,
                media: \is_string($config['media'] ?? null) ? $config['media'] : null,
                density: \is_string($config['density'] ?? null) ? $config['density'] : null,
                geometry: $geometry,
            );
        }

        return new VariantProcessingPlan(
            variants: $variants,
            formats: $formats,
            artifactCount: \count($variants) * \count($formats),
            outputPixels: $outputPixels,
        );
    }
}
