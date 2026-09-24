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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Upload\Upload\UploadProgress;

#[CoversClass(UploadProgress::class)]
final class UploadProgressTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $progress = new UploadProgress(
            uploadId: 'upload-123',
            storedChunks: 3,
            totalChunks: 5,
            percentComplete: 60,
            chunkIndices: [0, 1, 2],
        );

        self::assertSame('upload-123', $progress->uploadId);
        self::assertSame(3, $progress->storedChunks);
        self::assertSame(5, $progress->totalChunks);
        self::assertSame(60, $progress->percentComplete);
        self::assertSame([0, 1, 2], $progress->chunkIndices);
        self::assertSame('upload-123', $progress->getUploadId());
        self::assertSame(3, $progress->getStoredChunks());
        self::assertSame(5, $progress->getTotalChunks());
        self::assertSame(60, $progress->getPercentComplete());
        self::assertSame([0, 1, 2], $progress->getChunkIndices());
    }
}
