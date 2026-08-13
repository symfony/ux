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

use Symfony\UX\Upload\Exception\UploadSessionNotFoundException;

/**
 * Base class for storage implementations using the Template Method pattern.
 *
 * Provides session validation and assembly lifecycle orchestration.
 * Subclasses implement the actual storage operations via do*() methods.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
abstract class AbstractStorage implements StorageInterface
{
    public function __construct(
        protected readonly string $completedPrefix = '.tmp/completed',
    ) {
        $prefix = trim($this->completedPrefix, '/');
        if ('' === $prefix
            || str_starts_with($this->completedPrefix, '/')
            || str_contains($this->completedPrefix, "\0")
            || \in_array('..', explode('/', $prefix), true)
        ) {
            throw new \InvalidArgumentException('The completed upload prefix must be a non-empty relative storage path without ".." segments.');
        }
    }

    public function initiate(string $uploadId, array $metadata): void
    {
        $this->doInitiate($uploadId, $metadata);
    }

    public function assemble(string $uploadId, ?string $hashAlgorithm = null, ?int $expiresAt = null): AssembledUpload
    {
        $metadata = $this->getMetadata($uploadId);
        if (null === $metadata) {
            throw new UploadSessionNotFoundException($uploadId);
        }
        $expiresAt ??= isset($metadata['expiresAt']) && \is_int($metadata['expiresAt']) ? $metadata['expiresAt'] : time();

        return $this->doAssemble($uploadId, $metadata, $expiresAt, $hashAlgorithm);
    }

    public function isDistributed(): bool
    {
        return false;
    }

    /**
     * Initialize the upload session in the underlying storage.
     *
     * @param string               $uploadId Unique identifier for the upload session
     * @param array<string, mixed> $metadata Upload metadata
     */
    abstract protected function doInitiate(string $uploadId, array $metadata): void;

    /**
     * Assemble chunks into the final file in the underlying storage.
     *
     * Called after session existence has been validated. Implementations
     * must report the assembled size, and the hash when an algorithm is
     * given, computed during the write pass.
     *
     * @param string               $uploadId The upload session identifier
     * @param array<string, mixed> $metadata Upload metadata
     */
    abstract protected function doAssemble(string $uploadId, array $metadata, int $expiresAt, ?string $hashAlgorithm): AssembledUpload;

    /**
     * Assert that an upload session exists.
     *
     * @throws UploadSessionNotFoundException If the session does not exist
     */
    protected function assertSessionExists(string $uploadId): void
    {
        if (null === $this->getMetadata($uploadId)) {
            throw new UploadSessionNotFoundException($uploadId);
        }
    }

    /**
     * Generate a storage path for the assembled file.
     *
     * Uses one temporary prefix and an expiration timestamp in the key.
     */
    protected function generatePath(string $uploadId, string $filename, int $expiresAt): string
    {
        $extension = pathinfo($filename, \PATHINFO_EXTENSION);
        $extension = preg_replace('/[^a-zA-Z0-9]+/', '', $extension) ?? '';
        $suffix = '' !== $extension ? '.'.strtolower($extension) : '';

        return \sprintf('%s/%d-%s%s', trim($this->completedPrefix, '/'), $expiresAt, $uploadId, $suffix);
    }
}
