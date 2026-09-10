<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Exception;

final class ImageLimitExceededException extends ImageProcessingException
{
    public static function inputBytes(int $actual, int $limit): self
    {
        return new self(\sprintf('Image input size %d bytes exceeds the configured limit of %d bytes.', $actual, $limit));
    }

    public static function dimensions(int $width, int $height, int $maxWidth, int $maxHeight): self
    {
        return new self(\sprintf('Image dimensions %dx%d exceed the configured limit of %dx%d.', $width, $height, $maxWidth, $maxHeight));
    }

    public static function pixels(int $actual, int $limit): self
    {
        return new self(\sprintf('Image pixel count %d exceeds the configured limit of %d.', $actual, $limit));
    }

    public static function outputPixels(int $actual, int $limit): self
    {
        return new self(\sprintf('Generated image pixel count %d exceeds the configured output limit of %d.', $actual, $limit));
    }

    public static function variants(int $actual, int $limit): self
    {
        return new self(\sprintf('Image variant count %d exceeds the configured limit of %d.', $actual, $limit));
    }
}
