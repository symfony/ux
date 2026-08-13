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

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\UX\Upload\Event\UploadAssembledEvent;
use Symfony\UX\Upload\Event\UploadFailedEvent;
use Symfony\UX\Upload\Event\UploadProgressEvent;
use Symfony\UX\Upload\Event\UploadStartedEvent;
use Symfony\UX\Upload\Exception\InvalidArgumentException;
use Symfony\UX\Upload\Exception\ValidationException;
use Symfony\UX\Upload\Policy\UploadPolicy;
use Symfony\UX\Upload\Security\UploadContext;
use Symfony\UX\Upload\Storage\StorageInterface;
use Symfony\UX\Upload\Upload\CompletedUpload;
use Symfony\UX\Upload\Upload\CompletedUploadAccess;
use Symfony\UX\Upload\Upload\PendingUpload;
use Symfony\UX\Upload\Upload\UploadProgress;
use Symfony\UX\Upload\Url\UploadUrlGeneratorInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final readonly class Uploader implements UploaderInterface
{
    public const int DEFAULT_COMPLETED_TTL = 86400;
    public const int MAX_CHUNK_SIZE = 64 * 1024 * 1024;
    /**
     * Algorithms accepted for optional end-to-end file integrity verification.
     *
     * Single source of truth: the "integrity_algorithm" nodes of the bundle
     * configuration derive their allowed values from this list.
     *
     * @var list<string>
     */
    public const array INTEGRITY_ALGORITHMS = ['sha256', 'sha384', 'sha512'];
    /**
     * Default maximum upload size in bytes.
     *
     * Single source of truth for the default size limit: form defaults
     * ({@see Form\FileUploadType}) and the bundle
     * configuration derive their default from this value.
     */
    public const int DEFAULT_MAX_SIZE = 100 * 1024 * 1024;

    /** @var array{max_size: int, allowed_types: list<string>, chunk_size: int, integrity_algorithm: string, compression: bool} */
    private array $config;
    /**
     * Serializes concurrent writes of the same chunk (see {@see storeChunk()}).
     */
    private LockFactory $lockFactory;
    private CompletedUploadAccess $completedUploadAccess;

    /**
     * @param list<string> $allowedTypes
     */
    public function __construct(
        private StorageInterface $storage,
        private UploadUrlGeneratorInterface $urlGenerator,
        private EventDispatcherInterface $dispatcher,
        private string $name = 'default',
        private int $chunkSize = 5 * 1024 * 1024,
        private int $parallelChunks = 3,
        private bool $compressionEnabled = false,
        private int $maxSize = self::DEFAULT_MAX_SIZE,
        private array $allowedTypes = [],
        private string $integrityAlgorithm = 'sha256',
        ?LockFactory $lockFactory = null,
        private int $completedTtl = self::DEFAULT_COMPLETED_TTL,
        private int $maxPendingPerOwner = 1000,
        private bool $distributedLockGuaranteed = false,
        ?CompletedUploadAccess $completedUploadAccess = null,
    ) {
        if ('' === $this->name) {
            throw new InvalidArgumentException('The uploader name cannot be empty.');
        }
        if ($this->chunkSize < 1 || $this->chunkSize > self::MAX_CHUNK_SIZE) {
            throw new InvalidArgumentException(\sprintf('The chunk size must be between 1 and %d bytes.', self::MAX_CHUNK_SIZE));
        }
        if ($this->parallelChunks < 1 || $this->parallelChunks > 10) {
            throw new InvalidArgumentException('The number of parallel chunks must be between 1 and 10.');
        }
        if ($this->maxSize < 0) {
            throw new InvalidArgumentException('The maximum upload size cannot be negative.');
        }
        if ($this->completedTtl < 60) {
            throw new InvalidArgumentException('The completed upload TTL must be at least 60 seconds.');
        }
        if ($this->maxPendingPerOwner < 1) {
            throw new InvalidArgumentException('The pending upload quota must be greater than zero.');
        }
        if (!\in_array($this->integrityAlgorithm, self::INTEGRITY_ALGORITHMS, true)) {
            throw new InvalidArgumentException(\sprintf('Unsupported integrity algorithm "%s".', $this->integrityAlgorithm));
        }

        $this->completedUploadAccess = $completedUploadAccess ?? new CompletedUploadAccess($this->storage);
        // Default to a filesystem-backed lock so overwrite protection holds out
        // of the box. Applications that configure "framework.lock" get their
        // configured store injected instead, which is required for distributed
        // deployments.
        $this->lockFactory = $lockFactory ?? new LockFactory(new FlockStore());
        $this->config = [
            'max_size' => $this->maxSize,
            'allowed_types' => $this->allowedTypes,
            'chunk_size' => $this->chunkSize,
            'integrity_algorithm' => $this->integrityAlgorithm,
            'compression' => $this->compressionEnabled,
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array{max_size: int, allowed_types: list<string>, chunk_size: int, integrity_algorithm: string, compression: bool}
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Initialize an upload session and return a PendingUpload.
     *
     * @throws InvalidArgumentException If file size is zero or negative
     */
    public function initializeUpload(string $filename, int $fileSize, string $mimeType, ?string $hash = null, ?string $hashAlgorithm = null, ?UploadContext $context = null, ?UploadPolicy $policy = null): PendingUpload
    {
        $context ??= new UploadContext();
        $lock = $this->acquireQuotaLock($context);
        try {
            return $this->initializeUploadUnlocked($filename, $fileSize, $mimeType, $hash, $hashAlgorithm, $context, $policy);
        } finally {
            $lock->release();
        }
    }

    public function uploadDirect(string $filename, int $fileSize, string $mimeType, string $data, ?string $hash = null, ?string $hashAlgorithm = null, ?string $digest = null, ?UploadContext $context = null, ?UploadPolicy $policy = null, bool $compressed = false): CompletedUpload
    {
        if ($fileSize > $this->chunkSize) {
            throw new ValidationException(\sprintf('Direct upload size %d bytes exceeds the single-request limit of %d bytes.', $fileSize, $this->chunkSize));
        }

        $pending = $this->initializeUpload($filename, $fileSize, $mimeType, $hash, $hashAlgorithm, $context, $policy);
        try {
            $this->storeChunk($pending->uploadId, 0, $data, $digest, $compressed);

            return $this->completeUpload($pending->uploadId);
        } catch (\Throwable $exception) {
            try {
                $this->cancelUpload($pending->uploadId);
            } catch (\Throwable) {
                // Preserve the original upload failure.
            }

            throw $exception;
        }
    }

    private function initializeUploadUnlocked(string $filename, int $fileSize, string $mimeType, ?string $hash, ?string $hashAlgorithm, UploadContext $context, ?UploadPolicy $policy): PendingUpload
    {
        if ($this->storage->countPendingByContext($context) >= $this->maxPendingPerOwner) {
            throw new ValidationException(\sprintf('Maximum number of %d pending uploads reached for this upload context.', $this->maxPendingPerOwner));
        }
        if ($fileSize <= 0) {
            throw new InvalidArgumentException('File size must be greater than zero.');
        }

        if ($this->maxSize > 0 && $fileSize > $this->maxSize) {
            throw new ValidationException(\sprintf('File size %d bytes exceeds maximum allowed size of %d bytes.', $fileSize, $this->maxSize));
        }

        if (null !== $hash) {
            $hashAlgorithm ??= 'sha256';
            if ($hashAlgorithm !== $this->integrityAlgorithm) {
                throw new ValidationException(\sprintf('Hash algorithm "%s" does not match configured integrity algorithm "%s".', $hashAlgorithm, $this->integrityAlgorithm));
            }
        }

        $uploadId = bin2hex(random_bytes(16));
        $totalChunks = (int) ceil($fileSize / $this->chunkSize);

        $createdAt = time();
        $metadata = [
            'filename' => $filename,
            'fileSize' => $fileSize,
            'mimeType' => $mimeType,
            'totalChunks' => $totalChunks,
            'createdAt' => $createdAt,
            'uploader' => $this->name,
            'hash' => $hash,
            'hashAlgorithm' => null !== $hash ? $this->integrityAlgorithm : null,
            'ownerId' => $context->ownerId,
            'tenantId' => $context->tenantId,
            'field' => $context->fieldName,
            'policyMaxSize' => $policy?->maxSize,
            'policyAllowedTypes' => $policy?->allowedTypes,
            'policyMaxFiles' => $policy?->maxFiles,
            'compression' => $this->compressionEnabled,
            'parallel' => $this->parallelChunks,
        ];

        $this->storage->initiate($uploadId, $metadata);

        $pendingUpload = new PendingUpload(
            uploadId: $uploadId,
            filename: $filename,
            fileSize: $fileSize,
            mimeType: $mimeType,
            totalChunks: $totalChunks,
            chunkSize: $this->chunkSize,
            compression: $this->compressionEnabled,
            parallel: $this->parallelChunks,
        );

        $uploadUrl = $this->urlGenerator->generateUploadUrl($uploadId);

        $this->dispatcher->dispatch(new UploadStartedEvent(
            uploadId: $uploadId,
            filename: $filename,
            fileSize: $fileSize,
            mimeType: $mimeType,
            totalChunks: $totalChunks,
            uploadUrl: $uploadUrl,
            chunkSize: $this->chunkSize,
            compression: $this->compressionEnabled,
            parallel: $this->parallelChunks,
        ));

        return $pendingUpload;
    }

    /**
     * Store a chunk.
     *
     * @param bool $compressed Whether the chunk body is gzip-compressed, as
     *                         declared by the transport (Content-Encoding
     *                         header or "contentEncoding" field). The bytes
     *                         are never sniffed: a chunk that merely starts
     *                         with the gzip magic bytes is stored verbatim.
     *
     * @throws InvalidArgumentException If chunk index is out of range
     */
    public function storeChunk(string $uploadId, int $chunkIndex, string $chunkData, ?string $digest = null, bool $compressed = false): void
    {
        $lifecycleLock = $this->acquireLifecycleLock($uploadId);
        try {
            $metadata = $this->storage->getMetadata($uploadId);
            /** @var int $totalChunks */
            $totalChunks = $metadata['totalChunks'] ?? 0;

            if ($chunkIndex < 0 || $chunkIndex >= $totalChunks) {
                throw new InvalidArgumentException(\sprintf('Chunk index %d is out of range [0, %d).', $chunkIndex, $totalChunks));
            }

            // Storage duplicate detection is exists-then-write on most backends:
            // without serialization, two concurrent requests for the same chunk
            // could both pass the existence check and both write. The lock is keyed
            // per (uploadId, chunkIndex) -- the finest granularity that closes the
            // race -- so distinct chunks of the same upload still store in parallel
            // (the whole point of parallel_chunks). It blocks rather than fails fast
            // so the loser waits for the winner, then observes the chunk already
            // exists and is rejected cleanly instead of corrupting the stored bytes.
            //
            $lockKey = \sprintf('ux_upload_chunk_%s_%d', $uploadId, $chunkIndex);
            $lock = $this->lockFactory->createLock($lockKey);
            $lock->acquire(true);

            try {
                if ($compressed) {
                    if (!$this->compressionEnabled) {
                        throw new ValidationException(\sprintf('Chunk %d is declared gzip-compressed but compression is not enabled for this uploader.', $chunkIndex));
                    }
                    $chunkData = $this->decompressBounded($chunkData, $chunkIndex);
                }

                if (\strlen($chunkData) > $this->chunkSize) {
                    throw new ValidationException(\sprintf('Decompressed chunk %d size of %d bytes exceeds maximum allowed chunk size of %d bytes.', $chunkIndex, \strlen($chunkData), $this->chunkSize));
                }
                $actualDigest = hash('sha256', $chunkData);
                if (null !== $digest && !hash_equals(strtolower($digest), $actualDigest)) {
                    throw new ValidationException(\sprintf('Integrity check failed for chunk %d.', $chunkIndex));
                }

                $this->storage->storeChunk($uploadId, $chunkIndex, $chunkData, $actualDigest);
            } finally {
                $lock->release();
            }

            // Calculate progress
            $chunks = $this->storage->listChunks($uploadId);
            $storedChunks = \count($chunks);

            $this->dispatcher->dispatch(new UploadProgressEvent(
                uploadId: $uploadId,
                chunkIndex: $chunkIndex,
                totalChunks: $totalChunks,
                percentComplete: $this->getPercentComplete($storedChunks, $totalChunks),
                storedChunks: $storedChunks,
                chunkIndices: $chunks,
            ));
        } finally {
            $lifecycleLock->release();
        }
    }

    /**
     * Complete the upload by assembling chunks.
     */
    public function completeUpload(string $uploadId): CompletedUpload
    {
        $lock = $this->acquireLifecycleLock($uploadId);
        try {
            $path = null;
            try {
                $metadata = $this->storage->getMetadata($uploadId);
                if (null === $metadata) {
                    throw new InvalidArgumentException(\sprintf('Upload session "%s" does not exist.', $uploadId));
                }
                if (isset($metadata['completedPath']) && \is_string($metadata['completedPath'])) {
                    return $this->completedUploadFromMetadata($uploadId, $metadata);
                }

                /** @var string $filename */
                $filename = $metadata['filename'] ?? 'unknown';
                /** @var string $mimeType */
                $mimeType = $metadata['mimeType'] ?? 'application/octet-stream';
                /** @var int $fileSize */
                $fileSize = $metadata['fileSize'] ?? 0;
                /** @var string|null $expectedHash */
                $expectedHash = $metadata['hash'] ?? null;
                $hashAlgorithm = null;
                if (null !== $expectedHash && '' !== $expectedHash) {
                    $hashAlgorithm = $metadata['hashAlgorithm'] ?? 'sha256';
                    if (!\is_string($hashAlgorithm)) {
                        throw new ValidationException('Invalid file hash algorithm metadata.');
                    }
                }

                $completedAt = time();
                $completedExpiresAt = $completedAt + $this->completedTtl;

                $assembled = $this->storage->assemble($uploadId, $hashAlgorithm, $completedExpiresAt);
                $path = $assembled->path;
                $this->assertAssemblyIntegrity($assembled->size, $assembled->hash, $fileSize, $expectedHash);

                $createdAt = new \DateTimeImmutable()->setTimestamp(isset($metadata['createdAt']) && \is_int($metadata['createdAt']) ? $metadata['createdAt'] : time());
                $expiresAt = new \DateTimeImmutable()->setTimestamp($completedExpiresAt);
                $event = new UploadAssembledEvent(new CompletedUpload(
                    id: $uploadId,
                    uploader: $this->name,
                    path: $path,
                    originalName: $filename,
                    mimeType: $mimeType,
                    size: (int) $fileSize,
                    createdAt: $createdAt,
                    expiresAt: $expiresAt,
                    checksum: $expectedHash,
                    checksumAlgorithm: null !== $expectedHash ? $hashAlgorithm : null,
                    ownerId: isset($metadata['ownerId']) && \is_string($metadata['ownerId']) ? $metadata['ownerId'] : null,
                    tenantId: isset($metadata['tenantId']) && \is_string($metadata['tenantId']) ? $metadata['tenantId'] : null,
                    fieldName: isset($metadata['field']) && \is_string($metadata['field']) ? $metadata['field'] : null,
                    access: $this->completedUploadAccess,
                ), $metadata);
                $this->dispatcher->dispatch($event);
                $completed = $event->getUpload();
                $metadata['completedPath'] = $completed->getTemporaryPath();
                $metadata['completedMimeType'] = $completed->mimeType;
                $metadata['completedChecksum'] = $completed->checksum;
                $metadata['completedChecksumAlgorithm'] = $completed->checksumAlgorithm;
                $metadata['expiresAt'] = $completedExpiresAt;
                $this->storage->completeSession($uploadId, $metadata);

                return $completed;
            } catch (\Throwable $e) {
                if ($e instanceof ValidationException) {
                    // A policy or integrity rejection is definitive. Transient
                    // storage and listener failures keep the session so the
                    // client can retry completion without retransmitting bytes.
                    if (null !== $path && $this->storage->exists($path)) {
                        $this->storage->delete($path);
                    }
                    $this->storage->abort($uploadId);
                }

                $this->dispatcher->dispatch(new UploadFailedEvent($uploadId, $e));
                throw $e;
            }
        } finally {
            $lock->release();
        }
    }

    /**
     * Get progress information for an upload.
     */
    public function getProgress(string $uploadId): UploadProgress
    {
        $chunks = $this->storage->listChunks($uploadId);
        $storedChunks = \count($chunks);
        $metadata = $this->storage->getMetadata($uploadId);
        /** @var int $totalChunks */
        $totalChunks = $metadata['totalChunks'] ?? 0;

        return new UploadProgress(
            uploadId: $uploadId,
            storedChunks: $storedChunks,
            totalChunks: $totalChunks,
            percentComplete: $this->getPercentComplete($storedChunks, $totalChunks),
            chunkIndices: $chunks,
        );
    }

    /**
     * Cancel an in-progress upload.
     */
    public function cancelUpload(string $uploadId): void
    {
        $lock = $this->acquireLifecycleLock($uploadId);
        try {
            $metadata = $this->storage->getMetadata($uploadId);
            if (null === $metadata || !isset($metadata['completedPath'])) {
                $this->storage->abort($uploadId);
            }
        } finally {
            $lock->release();
        }
    }

    private function acquireLifecycleLock(string $uploadId): LockInterface
    {
        $this->assertDistributedLock();

        $lock = $this->lockFactory->createLock(\sprintf('ux_upload_lifecycle_%s', $uploadId));
        $lock->acquire(true);

        return $lock;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function completedUploadFromMetadata(string $uploadId, array $metadata): CompletedUpload
    {
        foreach (['completedPath', 'filename', 'fileSize', 'createdAt', 'expiresAt'] as $key) {
            if (!isset($metadata[$key])) {
                throw new ValidationException(\sprintf('Completed upload metadata "%s" is missing.', $key));
            }
        }

        return new CompletedUpload(
            id: $uploadId,
            uploader: isset($metadata['uploader']) && \is_string($metadata['uploader']) ? $metadata['uploader'] : $this->name,
            path: \is_string($metadata['completedPath']) ? $metadata['completedPath'] : throw new ValidationException('Invalid completed upload path metadata.'),
            originalName: \is_string($metadata['filename']) ? $metadata['filename'] : throw new ValidationException('Invalid completed upload filename metadata.'),
            mimeType: isset($metadata['completedMimeType']) && \is_string($metadata['completedMimeType'])
                ? $metadata['completedMimeType']
                : (\is_string($metadata['mimeType'] ?? null) ? $metadata['mimeType'] : 'application/octet-stream'),
            size: \is_int($metadata['fileSize']) ? $metadata['fileSize'] : throw new ValidationException('Invalid completed upload size metadata.'),
            createdAt: new \DateTimeImmutable()->setTimestamp(\is_int($metadata['createdAt']) ? $metadata['createdAt'] : throw new ValidationException('Invalid completed upload creation metadata.')),
            expiresAt: new \DateTimeImmutable()->setTimestamp(\is_int($metadata['expiresAt']) ? $metadata['expiresAt'] : throw new ValidationException('Invalid completed upload expiry metadata.')),
            checksum: isset($metadata['completedChecksum']) && \is_string($metadata['completedChecksum']) ? $metadata['completedChecksum'] : null,
            checksumAlgorithm: isset($metadata['completedChecksumAlgorithm']) && \is_string($metadata['completedChecksumAlgorithm']) ? $metadata['completedChecksumAlgorithm'] : null,
            ownerId: isset($metadata['ownerId']) && \is_string($metadata['ownerId']) ? $metadata['ownerId'] : null,
            tenantId: isset($metadata['tenantId']) && \is_string($metadata['tenantId']) ? $metadata['tenantId'] : null,
            fieldName: isset($metadata['field']) && \is_string($metadata['field']) ? $metadata['field'] : null,
            access: $this->completedUploadAccess,
        );
    }

    private function acquireQuotaLock(UploadContext $context): LockInterface
    {
        $this->assertDistributedLock();

        $key = hash('sha256', $this->name."\0".$context->fingerprint());
        $lock = $this->lockFactory->createLock('ux_upload_quota_'.$key);
        $lock->acquire(true);

        return $lock;
    }

    private function assertDistributedLock(): void
    {
        if ($this->storage->isDistributed() && !$this->distributedLockGuaranteed) {
            throw new ValidationException('This distributed upload storage requires a configured shared lock.');
        }
    }

    private function assertAssemblyIntegrity(int $actualSize, ?string $actualHash, int $expectedSize, ?string $expectedHash): void
    {
        if ($actualSize !== $expectedSize) {
            throw new ValidationException(\sprintf('Assembled file size %d bytes does not match declared file size of %d bytes.', $actualSize, $expectedSize));
        }
        if (null !== $expectedHash && '' !== $expectedHash && (null === $actualHash || !hash_equals($expectedHash, $actualHash))) {
            throw new ValidationException(\sprintf('File integrity check failed: expected hash "%s", got "%s".', $expectedHash, $actualHash ?? 'none'));
        }
    }

    private function getPercentComplete(int $storedChunks, int $totalChunks): int
    {
        if (0 === $totalChunks) {
            return 0;
        }

        return (int) round(($storedChunks / $totalChunks) * 100);
    }

    private function decompressBounded(string $compressed, int $chunkIndex): string
    {
        $context = @inflate_init(\ZLIB_ENCODING_GZIP);
        if (false === $context) {
            throw new ValidationException(\sprintf('Failed to initialize gzip decompression for chunk %d.', $chunkIndex));
        }

        $output = '';
        $length = \strlen($compressed);
        $blockSize = 64 * 1024;
        for ($offset = 0; $offset < $length; $offset += $blockSize) {
            $flush = $offset + $blockSize >= $length ? \ZLIB_FINISH : \ZLIB_SYNC_FLUSH;
            $decoded = @inflate_add($context, substr($compressed, $offset, $blockSize), $flush);
            if (false === $decoded) {
                throw new ValidationException(\sprintf('Failed to decompress gzip chunk %d. The data appears to be corrupted.', $chunkIndex));
            }
            if (\strlen($output) + \strlen($decoded) > $this->chunkSize) {
                throw new ValidationException(\sprintf('Decompressed chunk %d size of %d bytes exceeds maximum allowed chunk size of %d bytes.', $chunkIndex, \strlen($output) + \strlen($decoded), $this->chunkSize));
            }
            $output .= $decoded;
        }

        return $output;
    }
}
