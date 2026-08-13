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

/**
 * Represents an initialized upload session before chunks are sent.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final readonly class PendingUpload
{
    public function __construct(
        public string $uploadId,
        public string $filename,
        public int $fileSize,
        public string $mimeType,
        public int $totalChunks,
        public int $chunkSize,
        public bool $compression,
        public int $parallel,
    ) {
    }

    public function getId(): string
    {
        return $this->uploadId;
    }

    public function getUploadId(): string
    {
        return $this->uploadId;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getTotalChunks(): int
    {
        return $this->totalChunks;
    }

    public function getChunkSize(): int
    {
        return $this->chunkSize;
    }

    public function isCompressionEnabled(): bool
    {
        return $this->compression;
    }

    public function getParallelChunks(): int
    {
        return $this->parallel;
    }
}
