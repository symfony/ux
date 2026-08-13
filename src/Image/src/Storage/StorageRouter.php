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

use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\Image\Exception\RuntimeException;
use Symfony\UX\Image\ImageAsset;

/**
 * Routes each StorageInterface call to the backend configured for its storage name.
 *
 * The concrete backend cannot be selected once at container-build time: every
 * StorageInterface method carries a storage name at runtime (as a string
 * argument or via the ImageAsset), so the single StorageInterface alias must
 * dispatch to a different backend per call. Named storages declared with
 * "flysystem_service" or "adapter_service" resolve through the injected service
 * locator; any other name falls back to local disk storage.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class StorageRouter implements StreamStorageInterface
{
    public function __construct(
        private readonly ContainerInterface $backends,
        private readonly StorageInterface $fallback,
    ) {
    }

    public function store(UploadedFile $file, string $storageName, ?string $directory = null): string
    {
        return $this->resolve($storageName)->store($file, $storageName, $directory);
    }

    public function delete(ImageAsset $imageAsset): bool
    {
        return $this->resolve($imageAsset->storageName)->delete($imageAsset);
    }

    public function exists(ImageAsset $imageAsset): bool
    {
        return $this->resolve($imageAsset->storageName)->exists($imageAsset);
    }

    public function getPublicUrl(ImageAsset $imageAsset, ?string $variant = null): string
    {
        return $this->resolve($imageAsset->storageName)->getPublicUrl($imageAsset, $variant);
    }

    public function getFilePath(ImageAsset $imageAsset): string
    {
        return $this->resolve($imageAsset->storageName)->getFilePath($imageAsset);
    }

    public function readStream(string $storageName, StoragePath $path)
    {
        $backend = $this->streamBackend($storageName);

        return $backend->readStream($storageName, $path);
    }

    public function writeStream(string $storageName, StoragePath $path, $stream): void
    {
        $this->streamBackend($storageName)->writeStream($storageName, $path, $stream);
    }

    public function deletePath(string $storageName, StoragePath $path): void
    {
        $this->streamBackend($storageName)->deletePath($storageName, $path);
    }

    private function resolve(string $storageName): StorageInterface
    {
        new StorageName($storageName);
        if (!$this->backends->has($storageName)) {
            return $this->fallback;
        }

        $backend = $this->backends->get($storageName);
        \assert($backend instanceof StorageInterface);

        return $backend;
    }

    private function streamBackend(string $storageName): StreamStorageInterface
    {
        $backend = $this->resolve($storageName);
        if (!$backend instanceof StreamStorageInterface) {
            throw new RuntimeException(\sprintf('Image storage "%s" must implement %s to process variants.', $storageName, StreamStorageInterface::class));
        }

        return $backend;
    }
}
