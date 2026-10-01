<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload;

use Symfony\UX\Upload\Exception\ExceptionInterface;
use Symfony\UX\Upload\Policy\UploadPolicy;
use Symfony\UX\Upload\Security\UploadContext;
use Symfony\UX\Upload\Upload\CompletedUpload;
use Symfony\UX\Upload\Upload\PendingUpload;
use Symfony\UX\Upload\Upload\UploadProgress;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
interface UploaderInterface
{
    /**
     * Return the uploader name (matches the named uploader configuration key).
     */
    public function getName(): string;

    /**
     * Return the upload constraints and chunk size for the client.
     *
     * @return array{max_size: int, allowed_types: list<string>, chunk_size: int, integrity_algorithm: string, compression: bool}
     */
    public function getConfig(): array;

    /**
     * Start a new upload and return a pending upload with a unique ID.
     *
     * @throws ExceptionInterface
     */
    public function initializeUpload(string $filename, int $fileSize, string $mimeType, ?string $hash = null, ?string $hashAlgorithm = null, ?UploadContext $context = null, ?UploadPolicy $policy = null): PendingUpload;

    /**
     * Upload and complete a file that fits in one part.
     *
     * @throws ExceptionInterface
     */
    public function uploadDirect(string $filename, int $fileSize, string $mimeType, string $data, ?string $hash = null, ?string $hashAlgorithm = null, ?string $digest = null, ?UploadContext $context = null, ?UploadPolicy $policy = null, bool $compressed = false): CompletedUpload;

    /**
     * Store a single chunk of an in-progress upload.
     *
     * @throws ExceptionInterface
     */
    public function storeChunk(string $uploadId, int $chunkIndex, string $chunkData, ?string $digest = null, bool $compressed = false): void;

    /**
     * Assemble all chunks and finalize the upload.
     *
     * @throws ExceptionInterface
     */
    public function completeUpload(string $uploadId): CompletedUpload;

    /**
     * Abort an in-progress upload and clean up stored chunks.
     *
     * @throws ExceptionInterface
     */
    public function cancelUpload(string $uploadId): void;

    /**
     * Return the current progress of an in-progress upload.
     *
     * @throws ExceptionInterface
     */
    public function getProgress(string $uploadId): UploadProgress;
}
