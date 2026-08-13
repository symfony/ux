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

/**
 * Immutable value object representing an image asset.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final readonly class ImageAsset
{
    public const SCHEMA_VERSION = 1;

    /**
     * @param array<string, list<array<string, mixed>>> $variants
     */
    public function __construct(
        public string $storageName,
        public string $path,
        public ?string $originalFilename = null,
        public ?string $mimeType = null,
        public ?int $width = null,
        public ?int $height = null,
        public array $variants = [],
        public int $schemaVersion = self::SCHEMA_VERSION,
        public ?string $profile = null,
        public ?string $profileRevision = null,
    ) {
        new Storage\StorageName($storageName);
        // Absolute URLs remain supported for URL-only assets. Storage
        // implementations validate and confine paths before filesystem access.
        if (!str_contains($path, '://')) {
            Storage\StoragePath::fromAssetPath($path);
        }
        if (self::SCHEMA_VERSION !== $schemaVersion) {
            throw new Exception\InvalidArgumentException(\sprintf('Unsupported ImageAsset schema version "%d".', $schemaVersion));
        }
        if (null !== $width && $width < 1 || null !== $height && $height < 1) {
            throw new Exception\InvalidArgumentException('Image dimensions must be positive when provided.');
        }
        ImageSourceSet::fromArray($variants);
    }

    public function getStorageName(): string
    {
        return $this->storageName;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getOriginalFilename(): ?string
    {
        return $this->originalFilename;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function getVariants(): array
    {
        return $this->variants;
    }

    public function getSchemaVersion(): int
    {
        return $this->schemaVersion;
    }

    public function getProfile(): ?string
    {
        return $this->profile;
    }

    public function getProfileRevision(): ?string
    {
        return $this->profileRevision;
    }

    public function getImageSourceSet(): ImageSourceSet
    {
        return ImageSourceSet::fromArray($this->variants);
    }

    /**
     * Returns the list of available formats for this asset based on its variants.
     *
     * @return list<string>
     */
    public function getAvailableFormats(): array
    {
        return $this->getImageSourceSet()->getAvailableFormats();
    }

    /**
     * Check if the image has a specific variant.
     */
    public function hasVariant(string $variantName): bool
    {
        return null !== $this->getVariant($variantName);
    }

    /**
     * Get variant information.
     *
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
     * }|null
     */
    public function getVariant(string $variantName): ?array
    {
        foreach ($this->getImageSourceSet()->getAvailableFormats() as $format) {
            foreach ($this->getImageSourceSet()->getForFormat($format) as $candidate) {
                if ($candidate->name === $variantName) {
                    return $candidate->toArray();
                }
            }
        }

        return null;
    }

    public function getDefaultFormat(): ?string
    {
        $formats = $this->getAvailableFormats();

        if (!$formats) {
            return null;
        }

        foreach (['avif', 'webp', 'jpeg', 'jpg', 'png'] as $preferred) {
            if (\in_array($preferred, $formats, true)) {
                return $preferred;
            }
        }

        return $formats[0];
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
     * }|null
     */
    public function getPrimaryVariantForFormat(string $format): ?array
    {
        return $this->getImageSourceSet()->getPrimaryForFormat($format)?->toArray();
    }

    /**
     * @return list<string>
     */
    public function getFilePaths(): array
    {
        $paths = [$this->path];
        $sourceSet = $this->getImageSourceSet();
        foreach ($sourceSet->getAvailableFormats() as $format) {
            foreach ($sourceSet->getForFormat($format) as $variant) {
                $paths[] = $variant->path;
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * Convert to array for serialization.
     *
     * @return array{schemaVersion: int, storageName: string, path: string, originalFilename: string|null, mimeType: string|null, width: int|null, height: int|null, variants: array<string, list<array<string, mixed>>>, profile: string|null, profileRevision: string|null}
     */
    public function toArray(): array
    {
        return [
            'schemaVersion' => $this->schemaVersion,
            'storageName' => $this->storageName,
            'path' => $this->path,
            'originalFilename' => $this->originalFilename,
            'mimeType' => $this->mimeType,
            'width' => $this->width,
            'height' => $this->height,
            'variants' => $this->variants,
            'profile' => $this->profile,
            'profileRevision' => $this->profileRevision,
        ];
    }

    /**
     * Create from array (for deserialization).
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $storageName = $data['storageName'] ?? '';
        $path = $data['path'] ?? '';
        $originalFilename = $data['originalFilename'] ?? null;
        $mimeType = $data['mimeType'] ?? null;
        $width = $data['width'] ?? null;
        $height = $data['height'] ?? null;
        $rawVariants = \array_key_exists('variants', $data) ? $data['variants'] : [];
        if (!\is_array($rawVariants)) {
            throw new Exception\InvalidArgumentException('Image asset variants must use the format => list<variant> shape.');
        }
        ImageSourceSet::fromArray($rawVariants);
        /** @var array<string, list<array<string, mixed>>> $variants */
        $variants = $rawVariants;
        $schemaVersion = $data['schemaVersion'] ?? null;
        if (!\is_int($schemaVersion)) {
            throw new Exception\InvalidArgumentException('Image asset schemaVersion must be provided as an integer.');
        }
        $profile = $data['profile'] ?? null;
        $profileRevision = $data['profileRevision'] ?? null;
        if (null !== $profile && !\is_string($profile) || null !== $profileRevision && !\is_string($profileRevision)) {
            throw new Exception\InvalidArgumentException('Image asset profile metadata must be strings or null.');
        }

        if (!\is_string($storageName) || '' === trim($storageName) || !\is_string($path) || '' === trim($path)) {
            throw new Exception\InvalidArgumentException('An image asset requires a non-empty storage name and path.');
        }
        if (null !== $originalFilename && !\is_string($originalFilename) || null !== $mimeType && !\is_string($mimeType)) {
            throw new Exception\InvalidArgumentException('Image asset filename and mimeType must be strings or null.');
        }
        if (null !== $width && !\is_int($width) || null !== $height && !\is_int($height)) {
            throw new Exception\InvalidArgumentException('Image asset dimensions must be integers or null.');
        }

        return new self(
            storageName: $storageName,
            path: $path,
            originalFilename: $originalFilename,
            mimeType: $mimeType,
            width: $width,
            height: $height,
            variants: $variants,
            schemaVersion: $schemaVersion,
            profile: $profile,
            profileRevision: $profileRevision,
        );
    }
}
