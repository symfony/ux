<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LazyImage\BlurHash;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @experimental
 */
interface BlurHashInterface
{
    /**
     * Create and return a blurred thumbnail of the given image encoded as data URI.
     */
    public function createDataUriThumbnail(string $filename, int $width, int $height, int $encodingWidth = 75, int $encodingHeight = 75): string;

    /**
     * Encode the given image using the BlurHash algorithm.
     */
    public function encode(string $filename, int $encodingWidth = 75, int $encodingHeight = 75): string;
}
