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

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\Image\Exception\StorageException;
use Symfony\UX\Image\ImageAsset;
use Symfony\UX\Image\InspectedImage;
use Symfony\UX\Image\ProcessingLimits;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class LocalStorage implements StreamStorageInterface
{
    private Filesystem $filesystem;

    /** @param array<string, array<string, mixed>> $storages */
    public function __construct(
        private readonly array $storages,
        private readonly string $storageRoot,
        private readonly ?ProcessingLimits $limits = null,
    ) {
        $this->filesystem = new Filesystem();
    }

    public function store(UploadedFile $file, string $storageName, ?string $directory = null): string
    {
        $storageName = $this->validateStorageName($storageName);
        $source = $file->getRealPath() ?: $file->getPathname();
        $inspected = InspectedImage::fromPath($source, $this->limits);
        if ('svg' === $inspected->format) {
            throw new \Symfony\UX\Image\Exception\ImageProcessingException('SVG images are rejected by default.');
        }

        $filename = bin2hex(random_bytes(16)).'.'.$inspected->format;
        $relativeDir = null === $directory || '' === $directory ? '' : new StoragePath($directory)->value;
        $relativePath = ('' !== $relativeDir ? $relativeDir.'/' : '').$filename;

        $targetPath = $this->getFilePath(new ImageAsset($storageName, '/'.$relativePath));
        $targetDirectory = \dirname($targetPath);
        $this->filesystem->mkdir($targetDirectory);

        try {
            if ($file->isValid()) {
                $file->move($targetDirectory, $filename);
            } else {
                $this->filesystem->copy($file->getRealPath() ?: $file->getPathname(), $targetPath, true);
            }
        } catch (\Throwable $e) {
            throw StorageException::uploadFailed($file->getClientOriginalName(), $e->getMessage());
        }

        return '/'.$relativePath;
    }

    public function delete(ImageAsset $imageAsset): bool
    {
        $deleted = false;
        try {
            foreach ($imageAsset->getFilePaths() as $path) {
                $filePath = $this->getFilePath(new ImageAsset($imageAsset->storageName, $path));
                if (!$this->filesystem->exists($filePath)) {
                    continue;
                }
                $this->filesystem->remove($filePath);
                $deleted = true;
            }
        } catch (\Throwable $e) {
            throw StorageException::deletionFailed($imageAsset->path, $e->getMessage());
        }

        return $deleted;
    }

    public function exists(ImageAsset $imageAsset): bool
    {
        return $this->filesystem->exists($this->getFilePath($imageAsset));
    }

    public function getPublicUrl(ImageAsset $imageAsset, ?string $variant = null): string
    {
        $storage = $this->storages[$imageAsset->storageName] ?? [];
        $prefix = $storage['public_url_prefix'] ?? null;

        if (null === $prefix || !\is_string($prefix)) {
            return $imageAsset->path;
        }

        return rtrim($prefix, '/').'/'.ltrim($variant ?? $imageAsset->path, '/');
    }

    public function getFilePath(ImageAsset $imageAsset): string
    {
        $relative = StoragePath::fromAssetPath($imageAsset->path)->value;
        $storageName = $this->validateStorageName($imageAsset->storageName);
        $storageRoot = rtrim($this->storageRoot, '/').'/'.$storageName;
        $candidate = $storageRoot.'/'.$relative;
        $canonicalConfiguredRoot = $this->resolveWithExistingAncestor(rtrim($this->storageRoot, '/'));
        $canonicalStorageRoot = $this->resolveWithExistingAncestor($storageRoot);
        $canonicalCandidate = $this->resolveWithExistingAncestor($candidate);

        if (!$this->isPathWithin($canonicalStorageRoot, $canonicalConfiguredRoot)
            || !$this->isPathWithin($canonicalCandidate, $canonicalStorageRoot)) {
            throw new StorageException('The image path escapes its configured storage.');
        }

        return $candidate;
    }

    public function readStream(string $storageName, StoragePath $path)
    {
        $file = $this->getFilePath(new ImageAsset($storageName, '/'.$path->value));
        $stream = @fopen($file, 'r');
        if (!\is_resource($stream)) {
            throw StorageException::readFailed($path->value);
        }

        return $stream;
    }

    public function writeStream(string $storageName, StoragePath $path, $stream): void
    {
        if (!\is_resource($stream)) {
            throw new \Symfony\UX\Image\Exception\InvalidArgumentException('A storage write requires a stream resource.');
        }
        $target = $this->getFilePath(new ImageAsset($storageName, '/'.$path->value));
        $this->filesystem->mkdir(\dirname($target));
        $output = @fopen($target, 'x');
        if (!\is_resource($output)) {
            throw StorageException::writeFailed($path->value);
        }
        try {
            if (false === stream_copy_to_stream($stream, $output)) {
                throw StorageException::writeFailed($path->value);
            }
        } finally {
            fclose($output);
        }
    }

    public function deletePath(string $storageName, StoragePath $path): void
    {
        $this->filesystem->remove($this->getFilePath(new ImageAsset($storageName, '/'.$path->value)));
    }

    private function validateStorageName(string $storageName): string
    {
        $storageName = new StorageName($storageName)->value;
        if ('default_public' !== $storageName && !\array_key_exists($storageName, $this->storages)) {
            throw new \Symfony\UX\Image\Exception\InvalidArgumentException(\sprintf('Unknown image storage "%s". Configure it under "ux_image.storages".', $storageName));
        }

        return $storageName;
    }

    private function resolveWithExistingAncestor(string $path): string
    {
        $missing = [];
        $cursor = $path;
        while (false === ($resolved = realpath($cursor))) {
            $parent = \dirname($cursor);
            if ($parent === $cursor) {
                throw new StorageException(\sprintf('Could not resolve the configured storage path "%s".', $path));
            }
            array_unshift($missing, basename($cursor));
            $cursor = $parent;
        }

        return rtrim($resolved, \DIRECTORY_SEPARATOR).([] === $missing ? '' : \DIRECTORY_SEPARATOR.implode(\DIRECTORY_SEPARATOR, $missing));
    }

    private function isPathWithin(string $path, string $root): bool
    {
        return $path === $root || str_starts_with($path, rtrim($root, \DIRECTORY_SEPARATOR).\DIRECTORY_SEPARATOR);
    }
}
