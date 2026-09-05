<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image;

use Symfony\UX\Image\Exception\ImageLimitExceededException;

final class ProcessingLimits
{
    public function __construct(
        public readonly int $maxInputBytes = 20_000_000,
        public readonly int $maxWidth = 12_000,
        public readonly int $maxHeight = 12_000,
        public readonly int $maxPixels = 40_000_000,
        public readonly int $maxVariants = 12,
        public readonly int $maxOutputPixels = 80_000_000,
    ) {
        foreach (get_object_vars($this) as $name => $value) {
            if ($value < 1) {
                throw new Exception\InvalidArgumentException(\sprintf('Processing limit "%s" must be positive.', $name));
            }
        }
    }

    public function assertInput(int $bytes, int $width, int $height): void
    {
        if ($bytes > $this->maxInputBytes) {
            throw ImageLimitExceededException::inputBytes($bytes, $this->maxInputBytes);
        }
        if ($width > $this->maxWidth || $height > $this->maxHeight) {
            throw ImageLimitExceededException::dimensions($width, $height, $this->maxWidth, $this->maxHeight);
        }
        if ($width * $height > $this->maxPixels) {
            throw ImageLimitExceededException::pixels($width * $height, $this->maxPixels);
        }
    }

    public function assertOutputAllocation(int $width, int $height): void
    {
        if ($width > $this->maxWidth || $height > $this->maxHeight) {
            throw ImageLimitExceededException::dimensions($width, $height, $this->maxWidth, $this->maxHeight);
        }
        if ($width > intdiv($this->maxOutputPixels, $height)) {
            $actual = $width > intdiv(\PHP_INT_MAX, $height) ? \PHP_INT_MAX : $width * $height;

            throw ImageLimitExceededException::outputPixels($actual, $this->maxOutputPixels);
        }
    }
}
