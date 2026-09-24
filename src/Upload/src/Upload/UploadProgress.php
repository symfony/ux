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
 * Represents the current progress of a chunked upload.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class UploadProgress
{
    /**
     * @param array<int> $chunkIndices
     */
    public function __construct(
        public readonly string $uploadId,
        public readonly int $storedChunks,
        public readonly int $totalChunks,
        public readonly int $percentComplete,
        public readonly array $chunkIndices,
    ) {
    }

    public function getUploadId(): string
    {
        return $this->uploadId;
    }

    public function getStoredChunks(): int
    {
        return $this->storedChunks;
    }

    public function getTotalChunks(): int
    {
        return $this->totalChunks;
    }

    public function getPercentComplete(): int
    {
        return $this->percentComplete;
    }

    /**
     * @return array<int>
     */
    public function getChunkIndices(): array
    {
        return $this->chunkIndices;
    }
}
