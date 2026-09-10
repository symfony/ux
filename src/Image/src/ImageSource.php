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

use Symfony\UX\Image\Storage\StoragePath;

/**
 * Immutable value object representing one persisted image variant.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class ImageSource
{
    public function __construct(
        public readonly string $path,
        public readonly ?int $width = null,
        public readonly ?int $height = null,
        public readonly ?string $density = null,
        public readonly ?string $media = null,
        public readonly ?string $name = null,
        public readonly ?string $format = null,
        public readonly ?string $mimeType = null,
        public readonly ?string $mode = null,
        public readonly ?int $quality = null,
        public readonly ?string $position = null,
    ) {
        if ('' === trim($path)) {
            throw new Exception\InvalidArgumentException('An image variant requires a non-empty path.');
        }
        if (null !== $name && '' === trim($name) || null !== $format && '' === trim($format) || null !== $mimeType && '' === trim($mimeType)) {
            throw new Exception\InvalidArgumentException('Image variant name, format and mimeType must not be empty when provided.');
        }
        if (null !== $width && $width < 1 || null !== $height && $height < 1) {
            throw new Exception\InvalidArgumentException('Image variant dimensions must be positive.');
        }
        if (null !== $mode && !\in_array($mode, ['crop', 'fit', 'fill'], true)) {
            throw new Exception\InvalidArgumentException(\sprintf('Unsupported image variant resize mode "%s".', $mode));
        }
        if (null !== $quality && ($quality < 1 || $quality > 100)) {
            throw new Exception\InvalidArgumentException('Image variant quality must be between 1 and 100.');
        }
        if (null !== $position && '' === trim($position)) {
            throw new Exception\InvalidArgumentException('Image variant position must not be empty.');
        }
        if (null !== $density && (1 !== preg_match('/^(\d+(?:\.\d+)?)x$/', $density, $matches) || (float) $matches[1] <= 0)) {
            throw new Exception\InvalidArgumentException(\sprintf('Invalid image variant density "%s".', $density));
        }
        if (null !== $media && '' === trim($media)) {
            throw new Exception\InvalidArgumentException('Image variant media must not be empty.');
        }
    }

    public static function generated(
        string $name,
        StoragePath $path,
        string $format,
        string $mimeType,
        int $width,
        int $height,
        ?string $media = null,
        ?string $density = null,
        string $mode = 'fit',
        int $quality = 80,
        string $position = 'center',
    ): self {
        return new self(
            path: '/'.$path->value,
            width: $width,
            height: $height,
            density: $density,
            media: $media,
            name: $name,
            format: $format,
            mimeType: $mimeType,
            mode: $mode,
            quality: $quality,
            position: $position,
        );
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function getDensity(): ?string
    {
        return $this->density;
    }

    public function getMedia(): ?string
    {
        return $this->media;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function getMode(): ?string
    {
        return $this->mode;
    }

    public function getQuality(): ?int
    {
        return $this->quality;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['path']) || !\is_string($data['path'])) {
            throw new Exception\InvalidArgumentException('Image variant "path" must be a string.');
        }

        return new self(
            path: $data['path'],
            width: self::optionalInt($data, 'width'),
            height: self::optionalInt($data, 'height'),
            density: self::optionalString($data, 'density'),
            media: self::optionalString($data, 'media'),
            name: self::optionalString($data, 'name'),
            format: self::optionalString($data, 'format'),
            mimeType: self::optionalString($data, 'mimeType'),
            mode: self::optionalString($data, 'mode'),
            quality: self::optionalInt($data, 'quality'),
            position: self::optionalString($data, 'position'),
        );
    }

    /**
     * @return array{
     *     path: string,
     *     width: int|null,
     *     height: int|null,
     *     density: string|null,
     *     media: string|null,
     *     name?: string,
     *     format?: string,
     *     mimeType?: string,
     *     mode?: string,
     *     quality?: int,
     *     position?: string,
     * }
     */
    public function toArray(): array
    {
        $data = [
            'path' => $this->path,
            'width' => $this->width,
            'height' => $this->height,
            'density' => $this->density,
            'media' => $this->media,
        ];
        if (null !== $this->name) {
            $data['name'] = $this->name;
        }
        if (null !== $this->format) {
            $data['format'] = $this->format;
        }
        if (null !== $this->mimeType) {
            $data['mimeType'] = $this->mimeType;
        }
        if (null !== $this->mode) {
            $data['mode'] = $this->mode;
        }
        if (null !== $this->quality) {
            $data['quality'] = $this->quality;
        }
        if (null !== $this->position) {
            $data['position'] = $this->position;
        }

        return $data;
    }

    /**
     * @return array{name: string, path: string, format: string, mimeType: string, width: int, height: int, mode: string, quality: int, position: string, media: string|null, density: string|null}
     */
    public function toGeneratedArray(): array
    {
        if (null === $this->name || null === $this->format || null === $this->mimeType || null === $this->width || null === $this->height || null === $this->mode || null === $this->quality || null === $this->position) {
            throw new Exception\InvalidArgumentException('A generated image source requires complete transformation metadata.');
        }

        return [
            'name' => $this->name,
            'path' => $this->path,
            'format' => $this->format,
            'mimeType' => $this->mimeType,
            'width' => $this->width,
            'height' => $this->height,
            'mode' => $this->mode,
            'quality' => $this->quality,
            'position' => $this->position,
            'media' => $this->media,
            'density' => $this->density,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function optionalString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;
        if (null !== $value && !\is_string($value)) {
            throw new Exception\InvalidArgumentException(\sprintf('Image variant "%s" must be a string.', $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function optionalInt(array $data, string $key): ?int
    {
        $value = $data[$key] ?? null;
        if (null !== $value && !\is_int($value)) {
            throw new Exception\InvalidArgumentException(\sprintf('Image variant "%s" must be an integer.', $key));
        }

        return $value;
    }
}
