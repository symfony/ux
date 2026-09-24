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
 * Dispatched each time a chunk is persisted so observers can track incremental progress.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class UploadProgressEvent extends Event
{
    /**
     * @param array<int> $chunkIndices
     */
    public function __construct(
        private readonly string $uploadId,
        private readonly int $chunkIndex,
        private readonly int $totalChunks,
        private readonly int $percentComplete,
        private readonly int $storedChunks = 0,
        private readonly array $chunkIndices = [],
    ) {
    }

    public function getUploadId(): string
    {
        return $this->uploadId;
    }

    public function getChunkIndex(): int
    {
        return $this->chunkIndex;
    }

    public function getTotalChunks(): int
    {
        return $this->totalChunks;
    }

    public function getPercentComplete(): int
    {
        return $this->percentComplete;
    }

    public function getStoredChunks(): int
    {
        return $this->storedChunks;
    }

    /**
     * @return array<int>
     */
    public function getChunkIndices(): array
    {
        return $this->chunkIndices;
    }
}
