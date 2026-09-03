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

use Symfony\UX\Image\Exception\StorageException;

/**
 * Stages local encoded files and publishes them as one logical operation.
 */
final class ImageWriteSession
{
    /** @var array<string, string> */
    private array $staged = [];

    /** @var list<StoragePath> */
    private array $committed = [];

    public function __construct(
        private readonly StreamStorageInterface $storage,
        private readonly string $storageName,
    ) {
    }

    public function stage(StoragePath $path, string $localFile): void
    {
        if (!is_file($localFile)) {
            throw new \Symfony\UX\Image\Exception\InvalidArgumentException(\sprintf('Staged image "%s" does not exist.', $localFile));
        }
        $this->staged[$path->value] = $localFile;
    }

    public function commit(): void
    {
        try {
            foreach ($this->staged as $path => $localFile) {
                $stream = fopen($localFile, 'r');
                if (!\is_resource($stream)) {
                    throw StorageException::readFailed($localFile);
                }
                $storagePath = new StoragePath($path);
                try {
                    $this->storage->writeStream($this->storageName, $storagePath, $stream);
                } finally {
                    fclose($stream);
                }
                $this->committed[] = $storagePath;
            }
        } catch (\Throwable $e) {
            try {
                $this->rollback();
            } catch (\Throwable) {
                // Keep the write failure as the primary exception.
            }
            throw $e;
        }
    }

    public function rollback(): void
    {
        $failures = [];
        $committed = array_reverse($this->committed);
        $this->committed = [];

        foreach ($committed as $path) {
            try {
                $this->storage->deletePath($this->storageName, $path);
            } catch (\Throwable $e) {
                $failures[] = [$path, $e];
            }
        }

        if ([] !== $failures) {
            [$path, $failure] = $failures[0];

            throw new StorageException(\sprintf('Failed to roll back %d image path(s). First failure for "%s": %s', \count($failures), $path->value, $failure->getMessage()), 0, $failure);
        }
    }
}
