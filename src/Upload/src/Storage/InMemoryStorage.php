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

use Symfony\UX\Upload\Exception\InvalidArgumentException;
use Symfony\UX\Upload\Exception\StorageException;
use Symfony\UX\Upload\Security\UploadContext;

/**
 * Storage implementation that keeps files in memory.
 * Useful for testing.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class InMemoryStorage extends AbstractStorage implements PrunableStorageInterface
{
    /** @var array<string, string> */
    private array $files = [];

    /** @var array<string, array<int, string>> */
    private array $chunks = [];

    /** @var array<string, array<string, mixed>> */
    private array $metadata = [];

    public function __construct(string $completedPrefix = '.tmp/completed')
    {
        parent::__construct($completedPrefix);
    }

    public function write(string $path, mixed $content): void
    {
        if (\is_resource($content)) {
            $data = (string) stream_get_contents($content);
            rewind($content);
        } else {
            $data = (string) $content;
        }

        $this->files[$path] = $data;
    }

    /**
     * @return string
     */
    public function read(string $path): mixed
    {
        if (!$this->exists($path)) {
            throw new StorageException(\sprintf('File "%s" not found.', $path));
        }

        return $this->files[$path];
    }

    public function delete(string $path): void
    {
        unset($this->files[$path]);
    }

    public function exists(string $path): bool
    {
        return isset($this->files[$path]);
    }

    public function listChunks(string $uploadId): array
    {
        if (!isset($this->chunks[$uploadId])) {
            return [];
        }

        $indices = array_keys($this->chunks[$uploadId]);
        sort($indices);

        return $indices;
    }

    public function getMetadata(string $uploadId): ?array
    {
        return $this->metadata[$uploadId] ?? null;
    }

    public function abort(string $uploadId): void
    {
        unset($this->metadata[$uploadId]);
        unset($this->chunks[$uploadId]);
    }

    public function completeSession(string $uploadId, array $metadata): void
    {
        $this->metadata[$uploadId] = $metadata;
        $this->chunks[$uploadId] = [];
    }

    public function prune(int $maxAge): void
    {
        $cutoffTime = time() - $maxAge;

        foreach (array_keys($this->files) as $path) {
            if (str_starts_with($path, trim($this->completedPrefix, '/').'/')
                && preg_match('/^(\d+)-[a-f0-9]{32}(?:\.[a-zA-Z0-9]+)?$/', basename($path), $matches)
                && (int) $matches[1] <= time()
            ) {
                $this->delete($path);
            }
        }

        foreach ($this->metadata as $uploadId => $metadata) {
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
        }
    }

    protected function doInitiate(string $uploadId, array $metadata): void
    {
        $this->metadata[$uploadId] = $metadata;
        $this->chunks[$uploadId] = [];
    }

    public function storeChunk(string $uploadId, int $index, string $data, string $digest): ChunkWriteResult
    {
        $this->assertSessionExists($uploadId);
        if (isset($this->chunks[$uploadId][$index])) {
            if (hash_equals(hash('sha256', $this->chunks[$uploadId][$index]), $digest)) {
                return ChunkWriteResult::AlreadyPresent;
            }

            throw new InvalidArgumentException(\sprintf('Chunk %d has already been uploaded with different content.', $index));
        }
        $this->chunks[$uploadId][$index] = $data;

        return ChunkWriteResult::Stored;
    }

    public function countPendingByContext(UploadContext $context): int
    {
        $count = 0;
        foreach ($this->metadata as $metadata) {
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

    protected function doAssemble(string $uploadId, array $metadata, int $expiresAt, ?string $hashAlgorithm): AssembledUpload
    {
        $totalChunks = $metadata['totalChunks'] ?? 0;
        $content = '';

        for ($i = 0; $i < $totalChunks; ++$i) {
            if (!isset($this->chunks[$uploadId][$i])) {
                throw new StorageException('Missing chunk: '.$i);
            }
            $content .= $this->chunks[$uploadId][$i];
        }

        /** @var string $filename */
        $filename = $metadata['filename'] ?? '';
        $path = $this->generatePath($uploadId, $filename, $expiresAt);
        $this->write($path, $content);

        return new AssembledUpload(
            $path,
            \strlen($content),
            null !== $hashAlgorithm ? hash($hashAlgorithm, $content) : null,
        );
    }
}
