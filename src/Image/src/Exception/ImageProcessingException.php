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

/**
 * Exception thrown when image processing operations fail.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
class ImageProcessingException extends RuntimeException
{
    public static function processingFailed(string $operation, string $reason = ''): self
    {
        $message = \sprintf('Image processing operation "%s" failed', $operation);
        if ($reason) {
            $message .= ': '.$reason;
        }

        return new self($message);
    }

    public static function unsupportedFormat(string $format): self
    {
        return new self(\sprintf('Unsupported image format: %s', $format));
    }

    public static function invalidDimensions(int $width, int $height): self
    {
        return new self(\sprintf('Invalid image dimensions: %dx%d', $width, $height));
    }
}
