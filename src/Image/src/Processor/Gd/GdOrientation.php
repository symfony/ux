<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Processor\Gd;

use Symfony\UX\Image\Exception\ImageProcessingException;
use Symfony\UX\Image\Processor\ExifOrientation;

/**
 * Bakes an EXIF orientation into GD pixels.
 *
 * @internal
 */
final class GdOrientation
{
    public static function apply(ExifOrientation $orientation, \GdImage $image): \GdImage
    {
        return match ($orientation->value) {
            2 => self::flip($image),
            3 => self::rotate($image, 180),
            4 => self::flip(self::rotate($image, 180)),
            5 => self::flip(self::rotate($image, 270)),
            6 => self::rotate($image, 270),
            7 => self::flip(self::rotate($image, 90)),
            8 => self::rotate($image, 90),
            default => $image,
        };
    }

    private static function rotate(\GdImage $image, int $degrees): \GdImage
    {
        $rotated = imagerotate($image, $degrees, 0);
        if (!$rotated instanceof \GdImage) {
            throw ImageProcessingException::processingFailed('EXIF orientation', 'GD could not rotate the JPEG.');
        }

        return $rotated;
    }

    private static function flip(\GdImage $image): \GdImage
    {
        imageflip($image, \IMG_FLIP_HORIZONTAL);

        return $image;
    }
}
