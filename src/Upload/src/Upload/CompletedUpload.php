<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Upload;

use Symfony\UX\Upload\Exception\InvalidArgumentException;
use Symfony\UX\Upload\Exception\UploadException;

/**
 * A completed upload that still lives in UX Upload's temporary storage.
 *
 * Metadata access never reads the stored object. Call openStream() explicitly
 * when application code is ready to copy the bytes into its own storage.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class CompletedUpload
{
    public function __construct(
        public readonly string $id,
        public readonly string $uploader,
        private readonly string $path,
        public readonly string $originalName,
        public readonly string $mimeType,
        public readonly int $size,
        public readonly \DateTimeImmutable $createdAt,
        public readonly \DateTimeImmutable $expiresAt,
        public readonly ?string $checksum = null,
        public readonly ?string $checksumAlgorithm = null,
        private readonly ?string $ownerId = null,
        private readonly ?string $tenantId = null,
        private readonly ?string $fieldName = null,
        private readonly ?CompletedUploadAccess $access = null,
    ) {
        if ('' === $id || '' === $uploader || '' === $path || '' === $originalName || '' === $mimeType) {
            throw new InvalidArgumentException('A completed upload requires non-empty id, uploader, path, original name and MIME type values.');
        }
        if ($size < 0) {
            throw new InvalidArgumentException('A completed upload size cannot be negative.');
        }
        if ($expiresAt <= $createdAt) {
            throw new InvalidArgumentException('A completed upload must expire after it was created.');
        }
        if ((null === $checksum) !== (null === $checksumAlgorithm)) {
            throw new InvalidArgumentException('A completed upload checksum and algorithm must be provided together.');
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUploaderName(): string
    {
        return $this->uploader;
    }

    /**
     * Returns the path inside UX Upload's temporary storage.
     *
     * Do not persist this path as an application-owned file reference.
     */
    public function getTemporaryPath(): string
    {
        return $this->path;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getChecksum(): ?string
    {
        return $this->checksum;
    }

    public function getChecksumAlgorithm(): ?string
    {
        return $this->checksumAlgorithm;
    }

    public function getOwnerId(): ?string
    {
        return $this->ownerId;
    }

    public function getTenantId(): ?string
    {
        return $this->tenantId;
    }

    public function getFieldName(): ?string
    {
        return $this->fieldName;
    }

    public function isExpired(?\DateTimeImmutable $now = null): bool
    {
        return $this->expiresAt <= ($now ?? new \DateTimeImmutable());
    }

    /**
     * Open the temporary bytes as a readable stream.
     *
     * This is the only method that fetches remote storage content.
     *
     * @return resource
     */
    public function openStream()
    {
        if ($this->isExpired()) {
            throw new UploadException(\sprintf('Completed upload "%s" has expired.', $this->id));
        }
        if (null === $this->access) {
            throw new UploadException(\sprintf('Completed upload "%s" is detached from its temporary storage.', $this->id));
        }

        return $this->access->openStream($this->path);
    }

    /**
     * Delete this temporary upload immediately.
     *
     * Expiration cleanup remains the fallback when this method is not called.
     */
    public function delete(): void
    {
        if (null === $this->access) {
            throw new UploadException(\sprintf('Completed upload "%s" is detached from its temporary storage.', $this->id));
        }

        $this->access->delete($this->id, $this->path);
    }

    public function withMimeType(string $mimeType): self
    {
        return new self(
            $this->id,
            $this->uploader,
            $this->path,
            $this->originalName,
            $mimeType,
            $this->size,
            $this->createdAt,
            $this->expiresAt,
            $this->checksum,
            $this->checksumAlgorithm,
            $this->ownerId,
            $this->tenantId,
            $this->fieldName,
            $this->access,
        );
    }
}
