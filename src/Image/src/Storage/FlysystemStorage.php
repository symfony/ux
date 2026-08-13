<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Storage;

use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\Image\Exception\StorageException;
use Symfony\UX\Image\ImageAsset;
use Symfony\UX\Image\InspectedImage;
use Symfony\UX\Image\ProcessingLimits;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class FlysystemStorage implements StreamStorageInterface
{
    public function __construct(
        private readonly FilesystemOperator $filesystem,
        private readonly string $publicUrlPrefix = '',
        private readonly ?ProcessingLimits $limits = null,
    ) {
    }

    public function store(UploadedFile $file, string $storageName, ?string $directory = null): string
    {
        new StorageName($storageName);
        $source = $file->getRealPath() ?: $file->getPathname();
        $inspected = InspectedImage::fromPath($source, $this->limits);
        if ('svg' === $inspected->format) {
            throw new \Symfony\UX\Image\Exception\ImageProcessingException('SVG images are rejected by default.');
        }

        $filename = bin2hex(random_bytes(16)).'.'.$inspected->format;
        $relativeDir = null === $directory || '' === $directory ? '' : new StoragePath($directory)->value;
        $path = ('' !== $relativeDir ? $relativeDir.'/' : '').$filename;

        $stream = @fopen($source, 'r');
        if (false === $stream) {
            throw StorageException::writeFailed($path, 'The source file could not be opened.');
        }

        try {
            $this->filesystem->writeStream($path, $stream);
        } finally {
            fclose($stream);
        }

        return '/'.$path;
    }

    public function delete(ImageAsset $imageAsset): bool
    {
        $deleted = false;
        foreach ($imageAsset->getFilePaths() as $path) {
            $relativePath = $this->extractRelativePath(new ImageAsset($imageAsset->storageName, $path));
            if (!$this->filesystem->fileExists($relativePath)) {
                continue;
            }
            $this->filesystem->delete($relativePath);
            $deleted = true;
        }

        return $deleted;
    }

    public function exists(ImageAsset $imageAsset): bool
    {
        return $this->filesystem->fileExists($this->extractRelativePath($imageAsset));
    }

    public function getPublicUrl(ImageAsset $imageAsset, ?string $variant = null): string
    {
        return rtrim($this->publicUrlPrefix, '/').'/'.ltrim($variant ?? $imageAsset->path, '/');
    }

    public function getFilePath(ImageAsset $imageAsset): string
    {
        return $this->extractRelativePath($imageAsset);
    }

    public function readStream(string $storageName, StoragePath $path)
    {
        new StorageName($storageName);
        $stream = $this->filesystem->readStream($path->value);
        if (!\is_resource($stream)) {
            throw StorageException::readFailed($path->value, \sprintf('Storage "%s" did not return a stream.', $storageName));
        }

        return $stream;
    }

    public function writeStream(string $storageName, StoragePath $path, $stream): void
    {
        new StorageName($storageName);
        if (!\is_resource($stream)) {
            throw new \Symfony\UX\Image\Exception\InvalidArgumentException('A storage write requires a stream resource.');
        }
        if ($this->filesystem->fileExists($path->value)) {
            throw StorageException::writeFailed($path->value, 'The immutable object already exists.');
        }
        $this->filesystem->writeStream($path->value, $stream);
    }

    public function deletePath(string $storageName, StoragePath $path): void
    {
        new StorageName($storageName);
        if ($this->filesystem->fileExists($path->value)) {
            $this->filesystem->delete($path->value);
        }
    }

    private function extractRelativePath(ImageAsset $imageAsset): string
    {
        return StoragePath::fromAssetPath($imageAsset->path)->value;
    }
}
