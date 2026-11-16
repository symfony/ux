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
final class PendingUpload
{
    public function __construct(
        public readonly string $uploadId,
        public readonly string $filename,
        public readonly int $fileSize,
        public readonly string $mimeType,
        public readonly int $totalChunks,
        public readonly int $chunkSize,
        public readonly bool $compression,
        public readonly int $parallel,
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
