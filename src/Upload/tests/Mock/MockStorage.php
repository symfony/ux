<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Mock;

use Symfony\UX\Upload\Exception\InvalidArgumentException;
use Symfony\UX\Upload\Security\UploadContext;
use Symfony\UX\Upload\Storage\AssembledUpload;
use Symfony\UX\Upload\Storage\ChunkWriteResult;
use Symfony\UX\Upload\Storage\PrunableStorageInterface;
use Symfony\UX\Upload\Storage\StorageInterface;

final class MockStorage implements StorageInterface, PrunableStorageInterface
{
    private array $files = [];
    private array $chunks = [];
    private array $metadata = [];

    public function write(string $path, mixed $stream): void
    {
        $this->files[$path] = (string) $stream;
    }

    public function read(string $path): mixed
    {
        return $this->files[$path] ?? '';
    }

    public function delete(string $path): void
    {
        unset($this->files[$path]);
    }

    public function exists(string $path): bool
    {
        return isset($this->files[$path]);
    }

    public function initiate(string $uploadId, array $metadata): void
    {
        $this->metadata[$uploadId] = $metadata;
    }

    public function storeChunk(string $uploadId, int $index, string $data, string $digest): ChunkWriteResult
    {
        if (isset($this->chunks[$uploadId][$index])) {
            if (hash_equals(hash('sha256', $this->chunks[$uploadId][$index]), $digest)) {
                return ChunkWriteResult::AlreadyPresent;
            }

            throw new InvalidArgumentException(\sprintf('Chunk %d has already been uploaded with different content.', $index));
        }
        $this->chunks[$uploadId][$index] = $data;

        return ChunkWriteResult::Stored;
    }

    public function listChunks(string $uploadId): array
    {
        return array_keys($this->chunks[$uploadId] ?? []);
    }

    public function getMetadata(string $uploadId): ?array
    {
        return $this->metadata[$uploadId] ?? null;
    }

    public function assemble(string $uploadId, ?string $hashAlgorithm = null, ?int $expiresAt = null): AssembledUpload
    {
        $filename = $this->metadata[$uploadId]['filename'] ?? 'unknown';
        $extension = pathinfo($filename, \PATHINFO_EXTENSION);
        $suffix = '' !== $extension ? '.'.$extension : '';
        $expiresAt ??= time();
        $path = \sprintf('.tmp/completed/%d-%s%s', $expiresAt, $uploadId, $suffix);

        // Actually concatenate chunks
        $content = '';
        $chunks = $this->chunks[$uploadId] ?? [];
        ksort($chunks);
        foreach ($chunks as $chunk) {
            $content .= $chunk;
        }

        $this->files[$path] = $content;

        return new AssembledUpload(
            $path,
            \strlen($content),
            null !== $hashAlgorithm ? hash($hashAlgorithm, $content) : null,
        );
    }

    public function abort(string $uploadId): void
    {
        unset($this->metadata[$uploadId], $this->chunks[$uploadId]);
    }

    public function completeSession(string $uploadId, array $metadata): void
    {
        $this->metadata[$uploadId] = $metadata;
        $this->chunks[$uploadId] = [];
    }

    public function countPendingByContext(UploadContext $context): int
    {
        $count = 0;
        foreach ($this->metadata as $metadata) {
            if (!isset($metadata['completedPath'])) {
                ++$count;
            }
        }

        return $count;
    }

    public function isDistributed(): bool
    {
        return false;
    }

    public function prune(int $maxAge): void
    {
        // No-op for mock unless we need to test it, but interface requires it
    }

    public function clear(): void
    {
        $this->files = [];
        $this->chunks = [];
        $this->metadata = [];
    }

    public function simulateFile(string $path, string $content): void
    {
        $this->files[$path] = $content;
    }
}
