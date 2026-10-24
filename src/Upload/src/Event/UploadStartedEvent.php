<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when an upload session is started.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class UploadStartedEvent extends Event
{
    public function __construct(
        private readonly string $uploadId,
        private readonly string $filename,
        private readonly int $fileSize,
        private readonly string $mimeType,
        private readonly int $totalChunks,
        private readonly ?string $uploadUrl = null,
        private readonly ?int $chunkSize = null,
        private readonly bool $compression = false,
        private readonly int $parallel = 1,
    ) {
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

    public function getUploadUrl(): ?string
    {
        return $this->uploadUrl;
    }

    public function getChunkSize(): ?int
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
