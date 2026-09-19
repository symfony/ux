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

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\Image\Exception\ExceptionInterface;
use Symfony\UX\Image\ImageAsset;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
interface StorageInterface
{
    /**
     * Store an uploaded file and return the storage path.
     *
     * @throws ExceptionInterface
     */
    public function store(UploadedFile $file, string $storageName, ?string $directory = null): string;

    /**
     * Delete the original and every generated variant from storage.
     *
     * Returns true when at least one object existed and was deleted.
     *
     * @throws ExceptionInterface
     */
    public function delete(ImageAsset $imageAsset): bool;

    /**
     * Check if a file exists in storage.
     *
     * @throws ExceptionInterface
     */
    public function exists(ImageAsset $imageAsset): bool;

    /**
     * Get the public URL for an image asset.
     *
     * @throws ExceptionInterface
     */
    public function getPublicUrl(ImageAsset $imageAsset, ?string $variant = null): string;

    /**
     * Get the backend-native path for an image asset.
     *
     * Local storage returns an absolute filesystem path. Remote adapters may
     * return an object key; use StreamStorageInterface for portable I/O.
     *
     * @throws ExceptionInterface
     */
    public function getFilePath(ImageAsset $imageAsset): string;
}
