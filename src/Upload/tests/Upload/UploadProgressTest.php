<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Upload;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Upload\Upload\UploadProgress;

final class UploadProgressTest extends TestCase
{
    #[Test]
    public function constructorSetsAllProperties(): void
    {
        $progress = new UploadProgress(
            uploadId: 'upload-123',
            storedChunks: 3,
            totalChunks: 5,
            percentComplete: 60,
            chunkIndices: [0, 1, 2],
        );

        $this->assertSame('upload-123', $progress->uploadId);
        $this->assertSame(3, $progress->storedChunks);
        $this->assertSame(5, $progress->totalChunks);
        $this->assertSame(60, $progress->percentComplete);
        $this->assertSame([0, 1, 2], $progress->chunkIndices);
        $this->assertSame('upload-123', $progress->getUploadId());
        $this->assertSame(3, $progress->getStoredChunks());
        $this->assertSame(5, $progress->getTotalChunks());
        $this->assertSame(60, $progress->getPercentComplete());
        $this->assertSame([0, 1, 2], $progress->getChunkIndices());
    }

    #[Test]
    public function hasOnlyReadonlyProperties(): void
    {
        $reflection = new \ReflectionClass(UploadProgress::class);

        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue($property->isReadOnly(), \sprintf('Property "%s" should be readonly.', $property->getName()));
        }
    }

    #[Test]
    public function zeroProgress(): void
    {
        $progress = new UploadProgress(
            uploadId: 'id',
            storedChunks: 0,
            totalChunks: 10,
            percentComplete: 0,
            chunkIndices: [],
        );

        $this->assertSame(0, $progress->storedChunks);
        $this->assertSame(0, $progress->percentComplete);
        $this->assertSame([], $progress->chunkIndices);
    }

    #[Test]
    public function completedProgress(): void
    {
        $progress = new UploadProgress(
            uploadId: 'id',
            storedChunks: 3,
            totalChunks: 3,
            percentComplete: 100,
            chunkIndices: [0, 1, 2],
        );

        $this->assertSame(3, $progress->storedChunks);
        $this->assertSame(3, $progress->totalChunks);
        $this->assertSame(100, $progress->percentComplete);
    }

    #[Test]
    public function chunkIndicesCanBeOutOfOrder(): void
    {
        $progress = new UploadProgress(
            uploadId: 'id',
            storedChunks: 3,
            totalChunks: 5,
            percentComplete: 60,
            chunkIndices: [2, 0, 4],
        );

        $this->assertSame([2, 0, 4], $progress->chunkIndices);
    }
}
