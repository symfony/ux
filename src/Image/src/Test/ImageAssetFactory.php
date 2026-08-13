<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Test;

use Symfony\UX\Image\ImageAsset;

/**
 * Creates complete, deterministic ImageAsset fixtures for application tests.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class ImageAssetFactory
{
    /**
     * @param non-empty-list<string> $formats
     * @param non-empty-list<int>    $widths
     */
    public static function responsive(
        string $storageName = 'test',
        string $path = '/fixtures/original.jpeg',
        array $formats = ['webp', 'jpeg'],
        array $widths = [320, 640, 1280],
        int $originalWidth = 1600,
        int $originalHeight = 1000,
    ): ImageAsset {
        if ([] === $formats || [] === $widths || $originalWidth < 1 || $originalHeight < 1) {
            throw new \Symfony\UX\Image\Exception\InvalidArgumentException('A responsive image fixture requires formats, widths and positive source dimensions.');
        }

        $variants = [];
        foreach ($formats as $format) {
            if (!\is_string($format) || '' === trim($format)) {
                throw new \Symfony\UX\Image\Exception\InvalidArgumentException('Image fixture formats must be non-empty strings.');
            }
            $normalizedFormat = 'jpg' === strtolower($format) ? 'jpeg' : strtolower($format);
            foreach ($widths as $width) {
                if (!\is_int($width) || $width < 1) {
                    throw new \Symfony\UX\Image\Exception\InvalidArgumentException('Image fixture widths must be positive integers.');
                }
                $height = max(1, (int) round($width * $originalHeight / $originalWidth));
                $variants[$normalizedFormat][] = [
                    'name' => $width.'w',
                    'path' => \sprintf('/fixtures/image-%d.%s', $width, $normalizedFormat),
                    'format' => $normalizedFormat,
                    'mimeType' => 'image/'.$normalizedFormat,
                    'width' => $width,
                    'height' => $height,
                    'mode' => 'fit',
                    'quality' => 80,
                    'position' => 'center',
                    'media' => null,
                    'density' => null,
                ];
            }
        }

        return new ImageAsset(
            storageName: $storageName,
            path: $path,
            originalFilename: basename($path),
            mimeType: 'image/jpeg',
            width: $originalWidth,
            height: $originalHeight,
            variants: $variants,
            profile: 'test_responsive',
            profileRevision: hash('sha256', serialize([$formats, $widths, $originalWidth, $originalHeight])),
        );
    }
}
