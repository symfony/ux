<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Glide;

use Intervention\Image\Interfaces\DriverInterface;
use Intervention\Image\Interfaces\ImageManagerInterface;

/**
 * Keeps the formats the installed driver can encode: GD without libavif, for one, cannot write AVIF.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class EncodableFormats
{
    /**
     * @param list<string> $formats
     *
     * @return list<string>
     */
    public static function filter(DriverInterface $driver, array $formats): array
    {
        return array_values(array_filter($formats, static fn (string $format): bool => $driver->supports('pjpg' === $format ? 'jpeg' : $format)));
    }

    public static function driverOf(ImageManagerInterface $manager): DriverInterface
    {
        return $manager->createImage(1, 1)->driver();
    }
}
