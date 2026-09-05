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

use Symfony\UX\Image\Exception\ImageProcessingException;

/**
 * EXIF orientation metadata for JPEG inputs.
 *
 * The value is read without requiring ext-exif: environments without the
 * extension keep orientation 1 and continue processing the encoded pixels.
 *
 * @internal
 */
final class ExifOrientation
{
    private function __construct(public int $value)
    {
    }

    public static function fromJpeg(string $path): self
    {
        if (!\function_exists('exif_read_data')) {
            return new self(1);
        }

        $data = @exif_read_data($path, 'IFD0', true);
        $orientation = 1;
        if (\is_array($data)) {
            $ifd0 = $data['IFD0'] ?? null;
            $candidate = \is_array($ifd0) ? ($ifd0['Orientation'] ?? null) : ($data['Orientation'] ?? null);
            if (\is_int($candidate)) {
                $orientation = $candidate;
            }
        }

        return new self($orientation >= 1 && $orientation <= 8 ? $orientation : 1);
    }

    /**
     * @return array{int, int}
     */
    public function displayDimensions(int $width, int $height): array
    {
        return \in_array($this->value, [5, 6, 7, 8], true) ? [$height, $width] : [$width, $height];
    }

    public function applyTo(\GdImage $image): \GdImage
    {
        return match ($this->value) {
            2 => $this->flip($image),
            3 => $this->rotate($image, 180),
            4 => $this->flip($this->rotate($image, 180)),
            5 => $this->flip($this->rotate($image, 270)),
            6 => $this->rotate($image, 270),
            7 => $this->flip($this->rotate($image, 90)),
            8 => $this->rotate($image, 90),
            default => $image,
        };
    }

    private function rotate(\GdImage $image, int $degrees): \GdImage
    {
        $rotated = imagerotate($image, $degrees, 0);
        if (!$rotated instanceof \GdImage) {
            throw ImageProcessingException::processingFailed('EXIF orientation', 'GD could not rotate the JPEG.');
        }

        return $rotated;
    }

    private function flip(\GdImage $image): \GdImage
    {
        imageflip($image, \IMG_FLIP_HORIZONTAL);

        return $image;
    }
}
