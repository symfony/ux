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

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile as HttpUploadedFile;
use Symfony\UX\Upload\Exception\InvalidArgumentException;
use Symfony\UX\Upload\Exception\UploadException;

/**
 * A completed upload that still lives in UX Upload's temporary storage.
 *
 * Metadata access never reads the stored object. The bytes are copied to a
 * local file the first time its path is requested, and openStream() reads
 * them without that copy.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class UploadedFile extends HttpUploadedFile
{
    private bool $loaded = false;

    public function __construct(
        private readonly string $id,
        private readonly string $uploader,
        private readonly string $path,
        string $originalName,
        string $mimeType,
        private readonly int $size,
        private readonly \DateTimeImmutable $createdAt,
        private readonly \DateTimeImmutable $expiresAt,
        private readonly ?string $checksum = null,
        private readonly ?string $checksumAlgorithm = null,
        private readonly ?string $ownerId = null,
        private readonly ?string $tenantId = null,
        private readonly ?string $fieldName = null,
        private readonly ?UploadedFileAccess $access = null,
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

        // HttpFoundation only trusts PHP's own upload directory unless the "test" flag is set
        parent::__construct(self::createLocalFile(), $originalName, $mimeType, \UPLOAD_ERR_OK, true);
    }

    public function __destruct()
    {
        if (is_file($path = parent::getPathname())) {
            unlink($path);
        }
    }

    public function __clone()
    {
        $this->loaded = false;
        \SplFileInfo::__construct(self::createLocalFile());
    }

    public function __toString(): string
    {
        return $this->getPathname();
    }

    public function getPathname(): string
    {
        $this->load();

        return parent::getPathname();
    }

    public function getRealPath(): string|false
    {
        $this->load();

        return parent::getRealPath();
    }

    public function move(string $directory, ?string $name = null): File
    {
        if ($this->loaded) {
            return parent::move($directory, $name);
        }

        $target = $this->getTargetFile($directory, $name);
        $source = $this->openStream();
        $destination = @fopen($target->getPathname(), 'w');
        try {
            if (false === $destination || false === stream_copy_to_stream($source, $destination)) {
                throw new FileException(\sprintf('Could not move the upload "%s" to "%s".', $this->id, $target->getPathname()));
            }
        } finally {
            fclose($source);
            if (false !== $destination) {
                fclose($destination);
            }
        }

        @chmod($target->getPathname(), 0o666 & ~umask());

        return $target;
    }

    public function openFile(string $mode = 'r', bool $useIncludePath = false, $context = null): \SplFileObject
    {
        $this->load();

        return parent::openFile($mode, $useIncludePath, $context);
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
    public function getStoragePath(): string
    {
        return $this->path;
    }

    /**
     * Returns the MIME type detected when the upload completed, without reading the file.
     */
    public function getMimeType(): string
    {
        return $this->getClientMimeType();
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

    private static function createLocalFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ux_upload_');
        if (false === $path) {
            throw new UploadException('Unable to create a local file for the upload.');
        }

        return $path;
    }

    private function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $path = parent::getPathname();
        $source = $this->openStream();
        $target = fopen($path, 'w');
        try {
            if (false === $target || false === stream_copy_to_stream($source, $target)) {
                throw new UploadException(\sprintf('Unable to copy completed upload "%s" to a local file.', $this->id));
            }
        } finally {
            fclose($source);
            if (false !== $target) {
                fclose($target);
            }
        }

        clearstatcache(true, $path);
        $this->loaded = true;
    }

    public function withMimeType(string $mimeType): self
    {
        return new self(
            $this->id,
            $this->uploader,
            $this->path,
            $this->getClientOriginalName(),
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
