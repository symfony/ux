<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Cropperjs\Intervention;

use Intervention\Image\ImageManager;

/**
 * Tells which major of intervention/image is installed.
 *
 * The three majors this bundle supports differ in the manager factory, the
 * decoding method, the downscaling method, the rotation direction and the
 * encoding method, so callers branch on the result of {@see self::major()}.
 *
 * @author Thomas <thomas@sctr.net>
 *
 * @internal
 */
final class InterventionImage
{
    public const V2 = 2;
    public const V3 = 3;
    public const V4 = 4;

    private static ?int $major = null;

    public static function major(): int
    {
        return self::$major ??= match (true) {
            method_exists(ImageManager::class, 'usingDriver') => self::V4,
            method_exists(ImageManager::class, 'withDriver') => self::V3,
            default => self::V2,
        };
    }

    /**
     * Drivers are passed as class names from v3 onwards, and as plain names in v2.
     */
    public static function supportsDriverClasses(): bool
    {
        return self::V2 !== self::major();
    }
}
