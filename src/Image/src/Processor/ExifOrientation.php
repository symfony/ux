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
}
