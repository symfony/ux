<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Transformation;

final class ResizeGeometryCalculator
{
    public function calculate(int $sourceWidth, int $sourceHeight, int $targetWidth, int $targetHeight, ResizeMode $mode, ?FocalPoint $focalPoint = null, bool $allowUpscale = false): ResizeGeometry
    {
        if ($sourceWidth < 1 || $sourceHeight < 1 || ($targetWidth < 1 && $targetHeight < 1)) {
            throw new \Symfony\UX\Image\Exception\InvalidArgumentException('Source and at least one target dimension must be positive.');
        }

        if ($targetWidth < 1) {
            $targetWidth = (int) round($sourceWidth * ($targetHeight / $sourceHeight));
        }
        if ($targetHeight < 1) {
            $targetHeight = (int) round($sourceHeight * ($targetWidth / $sourceWidth));
        }

        $focalPoint ??= new FocalPoint();
        if (ResizeMode::Crop === $mode) {
            $targetRatio = $targetWidth / $targetHeight;
            $sourceRatio = $sourceWidth / $sourceHeight;
            if ($sourceRatio > $targetRatio) {
                $sourceCropHeight = $sourceHeight;
                $sourceCropWidth = max(1, (int) round($sourceHeight * $targetRatio));
            } else {
                $sourceCropWidth = $sourceWidth;
                $sourceCropHeight = max(1, (int) round($sourceWidth / $targetRatio));
            }

            $scale = min($targetWidth / $sourceCropWidth, $targetHeight / $sourceCropHeight);
            if (!$allowUpscale) {
                $scale = min(1.0, $scale);
            }
            $sourceX = (int) round(($sourceWidth - $sourceCropWidth) * $focalPoint->x);
            $sourceY = (int) round(($sourceHeight - $sourceCropHeight) * $focalPoint->y);
            $destinationWidth = (int) round($sourceCropWidth * $scale);
            $destinationHeight = (int) round($sourceCropHeight * $scale);

            return new ResizeGeometry($destinationWidth, $destinationHeight, 0, 0, $destinationWidth, $destinationHeight, $sourceX, $sourceY, $sourceCropWidth, $sourceCropHeight);
        }

        $scale = min($targetWidth / $sourceWidth, $targetHeight / $sourceHeight);
        if (!$allowUpscale) {
            $scale = min(1.0, $scale);
        }
        $destinationWidth = max(1, (int) round($sourceWidth * $scale));
        $destinationHeight = max(1, (int) round($sourceHeight * $scale));
        $canvasWidth = ResizeMode::Fill === $mode ? $targetWidth : $destinationWidth;
        $canvasHeight = ResizeMode::Fill === $mode ? $targetHeight : $destinationHeight;

        return new ResizeGeometry(
            $canvasWidth,
            $canvasHeight,
            (int) floor(($canvasWidth - $destinationWidth) / 2),
            (int) floor(($canvasHeight - $destinationHeight) / 2),
            $destinationWidth,
            $destinationHeight,
            0,
            0,
            $sourceWidth,
            $sourceHeight,
        );
    }
}
