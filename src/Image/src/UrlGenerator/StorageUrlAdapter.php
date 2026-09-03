<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\UrlGenerator;

use Symfony\UX\Image\ImageAsset;
use Symfony\UX\Image\Storage\StorageInterface;

/**
 * Delegates URL generation to the selected storage backend.
 */
final class StorageUrlAdapter implements UrlAdapterInterface
{
    public function __construct(private StorageInterface $storage)
    {
    }

    public function resolve(string $path, array $storageConfig, array $variantConfig = [], ?string $storageName = null): string
    {
        if (null === $storageName || '' === $storageName) {
            throw new \Symfony\UX\Image\Exception\InvalidArgumentException('The storage URL adapter requires a storage name.');
        }

        $variantPath = isset($variantConfig['path']) && \is_string($variantConfig['path'])
            ? $variantConfig['path']
            : null;

        return $this->storage->getPublicUrl(new ImageAsset($storageName, $path), $variantPath);
    }

    public static function getName(): string
    {
        return 'storage';
    }
}
