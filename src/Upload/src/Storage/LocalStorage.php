<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Storage;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\UX\Upload\Exception\InvalidArgumentException;
use Symfony\UX\Upload\Exception\StorageException;
use Symfony\UX\Upload\Security\UploadContext;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class LocalStorage extends AbstractStorage implements PrunableStorageInterface
{
    private readonly LockFactory $lockFactory;

    public function __construct(
        private readonly string $directory,
        private readonly string $tempDir,
        private readonly Filesystem $filesystem = new Filesystem(),
        string $completedPrefix = '.tmp/completed',
        ?LockFactory $lockFactory = null,
    ) {
        parent::__construct($completedPrefix);
        $this->lockFactory = $lockFactory ?? new LockFactory(new FlockStore());
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    public function write(string $path, mixed $content): void
    {
        $fullPath = Path::join($this->directory, $path);

        // Ensure directory exists
        $this->filesystem->mkdir(\dirname($fullPath));

        $this->filesystem->dumpFile($fullPath, $content);
    }

    /**
     * @return resource
     */
    public function read(string $path): mixed
    {
        $fullPath = Path::join($this->directory, $path);

        if (!$this->exists($path)) {
            throw new StorageException(\sprintf('File "%s" not found.', $path));
        }

        $stream = @fopen($fullPath, 'r');
        if (false === $stream) {
            throw new StorageException(\sprintf('Cannot open file for reading: %s', $path));
        }

        return $stream;
    }

    public function delete(string $path): void
    {
        $this->filesystem->remove(Path::join($this->directory, $path));
    }

    public function exists(string $path): bool
    {
        return $this->filesystem->exists(Path::join($this->directory, $path));
    }

    public function listChunks(string $uploadId): array
    {
        $uploadDir = $this->getUploadDir($uploadId);

        if (!$this->filesystem->exists($uploadDir)) {
            return [];
        }

        $chunks = glob($uploadDir.'/chunk_*') ?: [];
        $chunkIndices = array_map(static fn (string $path) => (int) str_replace($uploadDir.'/chunk_', '', $path), $chunks);
        sort($chunkIndices);

        return $chunkIndices;
    }

    public function storeChunk(string $uploadId, int $index, string $data, string $digest): ChunkWriteResult
    {
        $this->assertSessionExists($uploadId);
        $path = $this->getUploadDir($uploadId).'/chunk_'.$index;
        $handle = @fopen($path, 'x');
        if (false === $handle) {
            $existing = @file_get_contents($path);
            if (false !== $existing && hash_equals($digest, hash('sha256', $existing))) {
                return ChunkWriteResult::AlreadyPresent;
            }

            throw new InvalidArgumentException(\sprintf('Chunk %d has already been uploaded with different content.', $index));
        }
        try {
            if (false === fwrite($handle, $data)) {
                throw new StorageException(\sprintf('Unable to write chunk %d.', $index));
            }
        } finally {
            fclose($handle);
        }

        return ChunkWriteResult::Stored;
    }

    public function getMetadata(string $uploadId): ?array
    {
        $uploadDir = $this->getUploadDir($uploadId);
        $metadataFile = $uploadDir.'/metadata.json';

        if (!file_exists($metadataFile)) {
            return null;
        }

        $metadata = json_decode((string) file_get_contents($metadataFile), true, 512, \JSON_THROW_ON_ERROR);
        if (!\is_array($metadata)) {
            throw new StorageException(\sprintf('Invalid upload metadata for "%s".', $uploadId));
        }

        $normalized = [];
        foreach ($metadata as $key => $value) {
            if (!\is_string($key)) {
                throw new StorageException(\sprintf('Invalid upload metadata for "%s".', $uploadId));
            }
            $normalized[$key] = $value;
        }

        return $normalized;
    }

    public function abort(string $uploadId): void
    {
        $uploadDir = $this->getUploadDir($uploadId);
        $this->filesystem->remove($uploadDir);
    }

    public function completeSession(string $uploadId, array $metadata): void
    {
        $uploadDir = $this->getUploadDir($uploadId);
        $this->filesystem->mkdir($uploadDir);
        foreach (glob($uploadDir.'/chunk_*') ?: [] as $chunk) {
            $this->filesystem->remove($chunk);
        }
        $this->filesystem->dumpFile($uploadDir.'/metadata.json', json_encode($metadata, \JSON_THROW_ON_ERROR));
    }

    public function prune(int $maxAge): void
    {
        $this->pruneCompletedFiles();
        $tempDir = $this->tempDir;

        if (!$this->filesystem->exists($tempDir)) {
            return;
        }

        $dirs = glob($tempDir.'/*', \GLOB_ONLYDIR) ?: [];
        $cutoffTime = time() - $maxAge;
        $completedDirectory = Path::join($this->directory, trim($this->completedPrefix, '/'));

        foreach ($dirs as $uploadDir) {
            if ($uploadDir === $completedDirectory) {
                continue;
            }
            $uploadId = basename($uploadDir);
            $lock = $this->acquirePruneLock($uploadId);
            if (null === $lock) {
                continue;
            }
            try {
                $metadataFile = $uploadDir.'/metadata.json';

                // Delete if metadata missing (invalid) or too old.
                if (!file_exists($metadataFile)) {
                    $this->abort($uploadId);
                    continue;
                }

                try {
                    /** @var array<string, mixed> $metadata */
                    $metadata = json_decode((string) file_get_contents($metadataFile), true, 512, \JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    $this->abort($uploadId);
                    continue;
                }
                $createdAt = $metadata['createdAt'] ?? 0;

                $expiresAt = $metadata['expiresAt'] ?? null;
                if (\is_int($expiresAt) && $expiresAt <= time()) {
                    if (\is_string($metadata['completedPath'] ?? null)) {
                        $this->delete($metadata['completedPath']);
                    }
                    $this->abort($uploadId);
                } elseif (!isset($metadata['completedPath']) && $createdAt < $cutoffTime) {
                    $this->abort($uploadId);
                }
            } finally {
                $lock->release();
            }
        }
    }

    public function countPendingByContext(UploadContext $context): int
    {
        $count = 0;
        foreach (glob($this->tempDir.'/*/metadata.json') ?: [] as $metadataFile) {
            try {
                /** @var array<string, mixed> $metadata */
                $metadata = json_decode((string) file_get_contents($metadataFile), true, 512, \JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                continue;
            }
            if (isset($metadata['completedPath'])) {
                continue;
            }
            if ($context->matches(
                \is_string($metadata['ownerId'] ?? null) ? $metadata['ownerId'] : null,
                \is_string($metadata['tenantId'] ?? null) ? $metadata['tenantId'] : null,
                \is_string($metadata['field'] ?? null) ? $metadata['field'] : null,
            )) {
                ++$count;
            }
        }

        return $count;
    }

    protected function doInitiate(string $uploadId, array $metadata): void
    {
        $uploadDir = $this->getUploadDir($uploadId);
        $this->filesystem->mkdir($uploadDir);

        $this->filesystem->dumpFile($uploadDir.'/metadata.json', json_encode($metadata, \JSON_THROW_ON_ERROR));
    }

    protected function doAssemble(string $uploadId, array $metadata, int $expiresAt, ?string $hashAlgorithm): AssembledUpload
    {
        return $this->assembleFromMetadata($uploadId, $metadata, $expiresAt, $hashAlgorithm);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function assembleFromMetadata(string $uploadId, array $metadata, int $expiresAt, ?string $hashAlgorithm = null): AssembledUpload
    {
        $uploadDir = $this->getUploadDir($uploadId);
        /** @var string $filename */
        $filename = $metadata['filename'] ?? '';
        $finalPath = $this->generatePath($uploadId, $filename, $expiresAt);
        $fullFinalPath = Path::join($this->directory, $finalPath);

        $this->filesystem->mkdir(\dirname($fullFinalPath));
        if ($this->filesystem->exists($fullFinalPath)) {
            // A completed session returns before assembly. An object found here
            // can only be an uncommitted remainder from an interrupted attempt.
            $this->filesystem->remove($fullFinalPath);
        }

        $partialPath = tempnam(\dirname($fullFinalPath), '.ux-upload-'.$uploadId.'-');
        if (false === $partialPath) {
            throw new StorageException(\sprintf('Cannot create temporary assembly file beside: %s', $fullFinalPath));
        }
        $finalFile = fopen($partialPath, 'w');
        if (false === $finalFile) {
            $this->filesystem->remove($partialPath);
            throw new StorageException(\sprintf('Cannot open file for writing: %s', $fullFinalPath));
        }
        /** @var int $totalChunks */
        $totalChunks = $metadata['totalChunks'] ?? 0;
        $hashContext = null !== $hashAlgorithm ? hash_init($hashAlgorithm) : null;
        $size = 0;
        $failure = null;

        try {
            for ($i = 0; $i < $totalChunks; ++$i) {
                $chunkPath = $uploadDir.'/chunk_'.$i;
                if (!file_exists($chunkPath)) {
                    throw new StorageException('Missing chunk: '.$i);
                }
                $chunkFile = @fopen($chunkPath, 'r');
                if (false === $chunkFile) {
                    throw new StorageException('Cannot open chunk: '.$i);
                }
                try {
                    while (!feof($chunkFile)) {
                        $chunk = fread($chunkFile, 64 * 1024);
                        if (false === $chunk) {
                            throw new StorageException('Cannot read chunk: '.$i);
                        }
                        if ('' === $chunk) {
                            continue;
                        }
                        if (\strlen($chunk) !== fwrite($finalFile, $chunk)) {
                            throw new StorageException('Cannot write assembled upload.');
                        }
                        $size += \strlen($chunk);
                        if (null !== $hashContext) {
                            hash_update($hashContext, $chunk);
                        }
                    }
                } finally {
                    fclose($chunkFile);
                }
            }
        } catch (\Throwable $exception) {
            $failure = $exception;
        } finally {
            fclose($finalFile);
        }
        if (null !== $failure) {
            $this->filesystem->remove($partialPath);
            throw $failure;
        }
        try {
            $this->filesystem->rename($partialPath, $fullFinalPath, true);
        } catch (\Throwable $exception) {
            $this->filesystem->remove($partialPath);
            throw new StorageException(\sprintf('Cannot publish assembled upload: %s', $finalPath), 0, $exception);
        }

        return new AssembledUpload(
            $finalPath,
            $size,
            null !== $hashContext ? hash_final($hashContext) : null,
        );
    }

    private function getUploadDir(string $uploadId): string
    {
        if ('' === $uploadId || '.' === $uploadId || '..' === $uploadId || !preg_match('/^[\w\-]+$/', $uploadId)) {
            throw new StorageException(\sprintf('Invalid upload ID: "%s".', $uploadId));
        }

        return Path::join($this->tempDir, $uploadId);
    }

    private function pruneCompletedFiles(): void
    {
        $directory = Path::join($this->directory, trim($this->completedPrefix, '/'));
        if (!$this->filesystem->exists($directory)) {
            return;
        }

        foreach (glob($directory.'/*') ?: [] as $path) {
            if (is_file($path) && preg_match('/^(\d+)-[a-f0-9]{32}(?:\.[a-zA-Z0-9]+)?$/', basename($path), $matches) && (int) $matches[1] <= time()) {
                $this->filesystem->remove($path);
            }
        }
    }

    private function acquirePruneLock(string $uploadId): ?LockInterface
    {
        $lock = $this->lockFactory->createLock(\sprintf('ux_upload_lifecycle_%s', $uploadId));

        return $lock->acquire(false) ? $lock : null;
    }
}
