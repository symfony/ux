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

use League\Flysystem\FilesystemOperator;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\UX\Upload\Exception\InvalidArgumentException;
use Symfony\UX\Upload\Exception\StorageException;
use Symfony\UX\Upload\Security\UploadContext;

/**
 * Storage adapter for League\Flysystem's FilesystemOperator.
 *
 * To use this backend, set `storage: flysystem` and point `flysystem_service`
 * at the ID of the Flysystem filesystem service to use, for example a service
 * defined by league/flysystem-bundle. The filesystem is referenced explicitly
 * by service ID, never selected by autowiring the FilesystemOperator type,
 * so it stays unambiguous when several named filesystems are configured.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class FlysystemStorage extends AbstractStorage implements PrunableStorageInterface
{
    private readonly LockFactory $lockFactory;

    public function isDistributed(): bool
    {
        return true;
    }

    public function __construct(
        private readonly FilesystemOperator $filesystem,
        private readonly ?string $assemblyTempDir = null,
        string $completedPrefix = '.tmp/completed',
        ?LockFactory $lockFactory = null,
    ) {
        parent::__construct($completedPrefix);
        $this->lockFactory = $lockFactory ?? new LockFactory(new FlockStore());
    }

    public function write(string $path, mixed $content): void
    {
        if (\is_resource($content)) {
            $this->filesystem->writeStream($path, $content);
        } else {
            $this->filesystem->write($path, (string) $content);
        }
    }

    /**
     * @return resource
     */
    public function read(string $path): mixed
    {
        return $this->filesystem->readStream($path);
    }

    public function delete(string $path): void
    {
        if ($this->filesystem->fileExists($path)) {
            $this->filesystem->delete($path);
        }
    }

    public function exists(string $path): bool
    {
        return $this->filesystem->fileExists($path);
    }

    public function listChunks(string $uploadId): array
    {
        if (null === $this->getMetadata($uploadId)) {
            return [];
        }
        $indices = [];
        foreach ($this->filesystem->listContents($this->getUploadDir($uploadId)) as $item) {
            $path = $item->path();
            if (preg_match('/chunk_(\d+)$/', $path, $matches)) {
                $indices[] = (int) $matches[1];
            }
        }

        sort($indices);

        return $indices;
    }

    public function storeChunk(string $uploadId, int $index, string $data, string $digest): ChunkWriteResult
    {
        $this->assertSessionExists($uploadId);
        $path = $this->getUploadDir($uploadId).'/chunk_'.$index;
        if ($this->filesystem->fileExists($path)) {
            $stream = $this->filesystem->readStream($path);
            try {
                $context = hash_init('sha256');
                hash_update_stream($context, $stream);
                $existingDigest = hash_final($context);
            } finally {
                fclose($stream);
            }
            if (hash_equals($existingDigest, $digest)) {
                return ChunkWriteResult::AlreadyPresent;
            }

            throw new InvalidArgumentException(\sprintf('Chunk %d has already been uploaded with different content.', $index));
        }

        $this->filesystem->write($path, $data);

        return ChunkWriteResult::Stored;
    }

    public function countPendingByContext(UploadContext $context): int
    {
        if (!$this->filesystem->directoryExists('.tmp')) {
            return 0;
        }

        $count = 0;
        foreach ($this->filesystem->listContents('.tmp', false) as $item) {
            if (!$item->isDir()) {
                continue;
            }
            try {
                $metadata = $this->getMetadata(basename($item->path()));
                if (null !== $metadata && !isset($metadata['completedPath']) && $context->matches(
                    \is_string($metadata['ownerId'] ?? null) ? $metadata['ownerId'] : null,
                    \is_string($metadata['tenantId'] ?? null) ? $metadata['tenantId'] : null,
                    \is_string($metadata['field'] ?? null) ? $metadata['field'] : null,
                )) {
                    ++$count;
                }
            } catch (\Throwable) {
                // Corrupt and orphan sessions are handled by prune().
            }
        }

        return $count;
    }

    public function getMetadata(string $uploadId): ?array
    {
        $uploadDir = $this->getUploadDir($uploadId);
        $metadataPath = $uploadDir.'/metadata.json';

        if (!$this->filesystem->fileExists($metadataPath)) {
            return null;
        }

        $metadata = json_decode($this->filesystem->read($metadataPath), true, 512, \JSON_THROW_ON_ERROR);
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
        $this->filesystem->deleteDirectory($uploadDir);
    }

    public function completeSession(string $uploadId, array $metadata): void
    {
        $uploadDir = $this->getUploadDir($uploadId);
        foreach ($this->filesystem->listContents($uploadDir) as $item) {
            if ($item->isFile() && preg_match('/\/chunk_\d+$/', $item->path())) {
                $this->filesystem->delete($item->path());
            }
        }
        $this->filesystem->write($uploadDir.'/metadata.json', json_encode($metadata, \JSON_THROW_ON_ERROR));
    }

    public function prune(int $maxAge): void
    {
        if (!$this->filesystem->directoryExists('.tmp')) {
            return;
        }
        $this->pruneCompletedFiles();

        $listing = $this->filesystem->listContents('.tmp');
        $cutoffTime = time() - $maxAge;

        foreach ($listing as $item) {
            if (!$item->isDir() || trim($this->completedPrefix, '/') === $item->path()) {
                continue;
            }

            $uploadId = basename($item->path());
            $lock = $this->acquirePruneLock($uploadId);
            if (null === $lock) {
                continue;
            }
            try {
                $metadataPath = $item->path().'/metadata.json';

                if (!$this->filesystem->fileExists($metadataPath)) {
                    $this->abort($uploadId);
                    continue;
                }

                try {
                    /** @var array<string, mixed> $metadata */
                    $metadata = json_decode($this->filesystem->read($metadataPath), true, 512, \JSON_THROW_ON_ERROR);
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
                } catch (\Throwable) {
                    $this->abort($uploadId);
                }
            } finally {
                $lock->release();
            }
        }
    }

    protected function doInitiate(string $uploadId, array $metadata): void
    {
        $uploadDir = $this->getUploadDir($uploadId);
        $this->filesystem->write($uploadDir.'/metadata.json', json_encode($metadata, \JSON_THROW_ON_ERROR));
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

        if ($this->filesystem->fileExists($finalPath)) {
            // Completion metadata is committed only after validation. An object
            // found before that point may be partial after a process crash.
            $this->filesystem->delete($finalPath);
        }

        $tempDir = $this->assemblyTempDir ?? sys_get_temp_dir();
        if (!is_dir($tempDir) && !@mkdir($tempDir, 0o777, true) && !is_dir($tempDir)) {
            throw new StorageException(\sprintf('Cannot create Flysystem assembly directory "%s".', $tempDir));
        }
        $tempPath = tempnam($tempDir, 'ux-upload-');
        if (false === $tempPath) {
            throw new StorageException('Cannot create temporary file for Flysystem assembly.');
        }
        $tempStream = fopen($tempPath, 'w+');
        if (false === $tempStream) {
            @unlink($tempPath);
            throw new StorageException('Cannot open temporary file for Flysystem assembly.');
        }
        /** @var int $totalChunks */
        $totalChunks = $metadata['totalChunks'] ?? 0;
        $hashContext = null !== $hashAlgorithm ? hash_init($hashAlgorithm) : null;
        $size = 0;

        try {
            for ($i = 0; $i < $totalChunks; ++$i) {
                $chunkPath = $uploadDir.'/chunk_'.$i;
                if (!$this->filesystem->fileExists($chunkPath)) {
                    throw new StorageException('Missing chunk: '.$i);
                }

                $chunkStream = $this->filesystem->readStream($chunkPath);
                try {
                    while (!feof($chunkStream)) {
                        $chunk = fread($chunkStream, 64 * 1024);
                        if (false === $chunk) {
                            throw new StorageException('Cannot read chunk: '.$i);
                        }
                        if ('' === $chunk) {
                            continue;
                        }
                        if (\strlen($chunk) !== fwrite($tempStream, $chunk)) {
                            throw new StorageException('Cannot write Flysystem assembly file.');
                        }
                        $size += \strlen($chunk);
                        if (null !== $hashContext) {
                            hash_update($hashContext, $chunk);
                        }
                    }
                } finally {
                    fclose($chunkStream);
                }
            }

            rewind($tempStream);
            $this->filesystem->writeStream($finalPath, $tempStream);
        } catch (\Throwable $exception) {
            try {
                $this->filesystem->delete($finalPath);
            } catch (\Throwable) {
                // Preserve the assembly failure; cleanup is best-effort.
            }
            throw $exception;
        } finally {
            fclose($tempStream);
            @unlink($tempPath);
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

        return '.tmp/'.$uploadId;
    }

    private function pruneCompletedFiles(): void
    {
        $prefix = trim($this->completedPrefix, '/');
        if (!$this->filesystem->directoryExists($prefix)) {
            return;
        }

        foreach ($this->filesystem->listContents($prefix) as $item) {
            if ($item->isFile() && preg_match('/^(\d+)-[a-f0-9]{32}(?:\.[a-zA-Z0-9]+)?$/', basename($item->path()), $matches) && (int) $matches[1] <= time()) {
                $this->filesystem->delete($item->path());
            }
        }
    }

    private function acquirePruneLock(string $uploadId): ?LockInterface
    {
        $lock = $this->lockFactory->createLock(\sprintf('ux_upload_lifecycle_%s', $uploadId));

        return $lock->acquire(false) ? $lock : null;
    }
}
