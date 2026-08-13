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

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\Image\InspectedImage;
use Symfony\UX\Image\ProcessingLimits;

/**
 * Utility to inspect image files and extract metadata.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class ImageInspector implements ImageInspectorInterface
{
    /**
     * @return array{width: ?int, height: ?int, mime: ?string, format: ?string}
     */
    public function inspect(string|File|UploadedFile $file): array
    {
        $path = $file instanceof File ? ($file->getRealPath() ?: $file->getPathname()) : $file;

        if (!file_exists($path)) {
            return [
                'width' => null,
                'height' => null,
                'mime' => null,
                'format' => null,
            ];
        }

        $size = @getimagesize($path);
        $mime = $size['mime'] ?? new \finfo(\FILEINFO_MIME_TYPE)->file($path) ?: null;

        $width = $size[0] ?? null;
        $height = $size[1] ?? null;
        if (\is_int($width) && \is_int($height) && \IMAGETYPE_JPEG === ($size[2] ?? null)) {
            [$width, $height] = ExifOrientation::fromJpeg($path)->displayDimensions($width, $height);
        }

        return [
            'width' => $width,
            'height' => $height,
            'mime' => $mime,
            'format' => $this->resolveFormat($mime),
        ];
    }

    public function inspectImage(string|File|UploadedFile $file, ?ProcessingLimits $limits = null): InspectedImage
    {
        $path = $file instanceof File ? ($file->getRealPath() ?: $file->getPathname()) : $file;

        return InspectedImage::fromPath($path, $limits);
    }

    private function resolveFormat(?string $mime): ?string
    {
        if (!$mime) {
            return null;
        }

        return match ($mime) {
            'image/jpeg' => 'jpeg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/avif' => 'avif',
            'image/gif' => 'gif',
            'image/svg+xml' => 'svg',
            default => null,
        };
    }
}
