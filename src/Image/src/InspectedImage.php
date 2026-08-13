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

use Symfony\UX\Image\Exception\ImageProcessingException;
use Symfony\UX\Image\Processor\ExifOrientation;

/**
 * Trusted metadata read from the image contents.
 */
final readonly class InspectedImage
{
    public function __construct(
        public string $format,
        public string $mimeType,
        public int $width,
        public int $height,
        public int $bytes,
    ) {
        if ($width < 1 || $height < 1 || $bytes < 1) {
            throw ImageProcessingException::processingFailed('inspect', 'The image has invalid dimensions or size.');
        }
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getBytes(): int
    {
        return $this->bytes;
    }

    public function getPixelCount(): int
    {
        return $this->pixelCount();
    }

    public function pixelCount(): int
    {
        return $this->width * $this->height;
    }

    public static function fromPath(string $path, ?ProcessingLimits $limits = null): self
    {
        if (!is_file($path)) {
            throw ImageProcessingException::processingFailed('inspect', \sprintf('File "%s" does not exist.', $path));
        }

        $size = @getimagesize($path);
        $mime = $size['mime'] ?? null;
        $format = match ($mime) {
            'image/jpeg' => 'jpeg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/avif' => 'avif',
            'image/svg+xml' => 'svg',
            default => null,
        };

        if (null === $format || !isset($size[0], $size[1])) {
            throw ImageProcessingException::unsupportedFormat($mime ?? 'unknown');
        }
        \assert(null !== $mime);

        [$width, $height] = \IMAGETYPE_JPEG === $size[2]
            ? ExifOrientation::fromJpeg($path)->displayDimensions($size[0], $size[1])
            : [$size[0], $size[1]];
        $bytes = filesize($path) ?: 0;
        ($limits ?? new ProcessingLimits())->assertInput($bytes, $width, $height);

        return new self($format, $mime, $width, $height, $bytes);
    }
}
