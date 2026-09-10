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

use Symfony\UX\Upload\Exception\StorageException;
use Symfony\UX\Upload\Security\UploadContext;

/**
 * Interface for file storage with chunked upload support.
 *
 * Combines basic file operations (read, write, delete, exists) with
 * chunked upload lifecycle management (initiate, storeChunk, assemble, abort).
 * Extend {@see AbstractStorage} to inherit default implementations of the
 * lifecycle orchestration and only provide the raw storage primitives.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
interface StorageInterface
{
    /**
     * Write content to storage.
     *
     * @param string          $path    Relative path within storage
     * @param string|resource $content Content to write (string or stream resource)
     *
     * @throws StorageException If write fails
     */
    public function write(string $path, mixed $content): void;

    /**
     * Read content from storage.
     *
     * @param string $path Relative path within storage
     *
     * @return string|resource File contents as string or stream resource
     *
     * @throws StorageException If file not found or read fails
     */
    public function read(string $path): mixed;

    /**
     * Delete a file from storage.
     *
     * @param string $path Relative path within storage
     *
     * @throws StorageException If delete fails
     */
    public function delete(string $path): void;

    /**
     * Check if a file exists in storage.
     *
     * @param string $path Relative path within storage
     */
    public function exists(string $path): bool;

    /**
     * Initialize a new chunked upload session.
     *
     * @param string               $uploadId Unique identifier for the upload session
     * @param array<string, mixed> $metadata Upload metadata with the following keys:
     *                                       - filename: string - Original filename
     *                                       - fileSize: int - Total file size in bytes
     *                                       - mimeType: string - MIME type (e.g., 'image/jpeg')
     *                                       - totalChunks: int - Expected number of chunks
     *                                       - hash: string|null - Optional file hash for verification
     *                                       - hashAlgorithm: string|null - Algorithm used for the optional file hash
     *                                       - ownerId: string|null - Optional stable owner identifier
     *                                       - tenantId: string|null - Optional stable tenant identifier
     *                                       - field: string|null - Optional field scope
     *                                       - createdAt: int - Unix timestamp of upload initiation
     *
     * @throws StorageException If session cannot be initialized
     */
    public function initiate(string $uploadId, array $metadata): void;

    /**
     * Store a single chunk of the upload.
     *
     * A chunk index may only be written once. A repeated write with the same
     * digest is an idempotent retry and returns ChunkWriteResult::AlreadyPresent;
     * a repeated write with a different digest must be rejected so stored bytes
     * are never overwritten.
     *
     * @param string $uploadId The upload session identifier
     * @param int    $index    Zero-based chunk index (0 <= index < totalChunks)
     * @param string $data     The chunk data
     * @param string $digest   Lowercase hex sha256 digest of the chunk data
     *
     * @throws StorageException If upload session not found or chunk cannot be stored
     */
    public function storeChunk(string $uploadId, int $index, string $data, string $digest): ChunkWriteResult;

    /**
     * Get indices of all stored chunks for an upload.
     *
     * @param string $uploadId The upload session identifier
     *
     * @return array<int> Sorted array of chunk indices that have been stored
     */
    public function listChunks(string $uploadId): array;

    /**
     * Get metadata for an upload session.
     *
     * @param string $uploadId The upload session identifier
     *
     * @return array<string, mixed>|null Metadata array or null if not found
     */
    public function getMetadata(string $uploadId): ?array;

    /**
     * Assemble all chunks into the final file.
     *
     * This method concatenates all chunks in order, stores the result and
     * reports the assembled size (and hash when an algorithm is given) computed
     * during the single write pass, so callers can verify integrity without
     * reading the object back. The caller finalizes session metadata with
     * completeSession().
     *
     * @param string      $uploadId      The upload session identifier
     * @param string|null $hashAlgorithm Hash algorithm to compute while assembling, or null to skip hashing
     * @param int|null    $expiresAt     Expiration timestamp for the completed temporary object
     *
     * @throws StorageException If chunks are missing or assembly fails
     */
    public function assemble(string $uploadId, ?string $hashAlgorithm = null, ?int $expiresAt = null): AssembledUpload;

    /**
     * Keep completion metadata for idempotent retries while deleting chunk data.
     *
     * @param array<string, mixed> $metadata
     */
    public function completeSession(string $uploadId, array $metadata): void;

    /**
     * Abort an upload session and clean up associated resources.
     *
     * @param string $uploadId The upload session identifier
     */
    public function abort(string $uploadId): void;

    /**
     * Count pending (initiated, not completed) upload sessions for a context.
     *
     * Used to enforce the "max_pending_per_owner" quota before initiating a
     * new session.
     */
    public function countPendingByContext(UploadContext $context): int;

    /**
     * Whether the storage is shared between hosts (object storage, NFS, ...).
     *
     * A distributed storage requires an explicitly configured shared lock
     * store; the default filesystem-based lock only protects a single host.
     */
    public function isDistributed(): bool;
}
