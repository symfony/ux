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
use Symfony\UX\Upload\Upload\PendingUpload;

#[CoversClass(PendingUpload::class)]
final class PendingUploadTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $pending = new PendingUpload(
            uploadId: 'upload-abc-123',
            filename: 'document.pdf',
            fileSize: 1_000_000,
            mimeType: 'application/pdf',
            totalChunks: 5,
            chunkSize: 200_000,
            compression: true,
            parallel: 3,
        );

        self::assertSame('upload-abc-123', $pending->uploadId);
        self::assertSame('document.pdf', $pending->filename);
        self::assertSame(1_000_000, $pending->fileSize);
        self::assertSame('application/pdf', $pending->mimeType);
        self::assertSame(5, $pending->totalChunks);
        self::assertSame(200_000, $pending->chunkSize);
        self::assertTrue($pending->compression);
        self::assertSame(3, $pending->parallel);
        self::assertSame('upload-abc-123', $pending->getId());
        self::assertSame('upload-abc-123', $pending->getUploadId());
        self::assertSame('document.pdf', $pending->getFilename());
        self::assertSame(1_000_000, $pending->getFileSize());
        self::assertSame('application/pdf', $pending->getMimeType());
        self::assertSame(5, $pending->getTotalChunks());
        self::assertSame(200_000, $pending->getChunkSize());
        self::assertTrue($pending->isCompressionEnabled());
        self::assertSame(3, $pending->getParallelChunks());
    }
}
