<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Event;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Upload\Event\UploadFailedEvent;
use Symfony\UX\Upload\Event\UploadProgressEvent;
use Symfony\UX\Upload\Event\UploadStartedEvent;

final class UploadEventsTest extends TestCase
{
    public function testStartedEventExposesTheTransferContract()
    {
        $event = new UploadStartedEvent(
            uploadId: 'upload-1',
            filename: 'report.csv',
            fileSize: 123,
            mimeType: 'text/csv',
            totalChunks: 3,
            uploadUrl: '/upload/upload-1',
            chunkSize: 64,
            compression: true,
            parallel: 2,
        );

        self::assertSame('upload-1', $event->getUploadId());
        self::assertSame('report.csv', $event->getFilename());
        self::assertSame(123, $event->getFileSize());
        self::assertSame('text/csv', $event->getMimeType());
        self::assertSame(3, $event->getTotalChunks());
        self::assertSame('/upload/upload-1', $event->getUploadUrl());
        self::assertSame(64, $event->getChunkSize());
        self::assertTrue($event->isCompressionEnabled());
        self::assertSame(2, $event->getParallelChunks());
    }

    public function testProgressEventExposesPersistedChunks()
    {
        $event = new UploadProgressEvent('upload-1', 2, 4, 75, 3, [0, 1, 2]);

        self::assertSame('upload-1', $event->getUploadId());
        self::assertSame(2, $event->getChunkIndex());
        self::assertSame(4, $event->getTotalChunks());
        self::assertSame(75, $event->getPercentComplete());
        self::assertSame(3, $event->getStoredChunks());
        self::assertSame([0, 1, 2], $event->getChunkIndices());
    }

    public function testFailedEventPreservesTheOriginalError()
    {
        $error = new \RuntimeException('storage unavailable');
        $event = new UploadFailedEvent('upload-1', $error);

        self::assertSame('upload-1', $event->getUploadId());
        self::assertSame($error, $event->getError());
    }
}
