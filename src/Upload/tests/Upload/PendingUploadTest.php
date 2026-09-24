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
use Symfony\UX\Upload\Upload\PendingUpload;

final class PendingUploadTest extends TestCase
{
    #[Test]
    public function constructorSetsAllProperties(): void
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

        $this->assertSame('upload-abc-123', $pending->uploadId);
        $this->assertSame('document.pdf', $pending->filename);
        $this->assertSame(1_000_000, $pending->fileSize);
        $this->assertSame('application/pdf', $pending->mimeType);
        $this->assertSame(5, $pending->totalChunks);
        $this->assertSame(200_000, $pending->chunkSize);
        $this->assertTrue($pending->compression);
        $this->assertSame(3, $pending->parallel);
        $this->assertSame('upload-abc-123', $pending->getId());
        $this->assertSame('upload-abc-123', $pending->getUploadId());
        $this->assertSame('document.pdf', $pending->getFilename());
        $this->assertSame(1_000_000, $pending->getFileSize());
        $this->assertSame('application/pdf', $pending->getMimeType());
        $this->assertSame(5, $pending->getTotalChunks());
        $this->assertSame(200_000, $pending->getChunkSize());
        $this->assertTrue($pending->isCompressionEnabled());
        $this->assertSame(3, $pending->getParallelChunks());
    }

    #[Test]
    public function hasOnlyReadonlyProperties(): void
    {
        $reflection = new \ReflectionClass(PendingUpload::class);

        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue($property->isReadOnly(), \sprintf('Property "%s" should be readonly.', $property->getName()));
        }
    }

    #[Test]
    public function acceptsCompressionDisabled(): void
    {
        $pending = new PendingUpload(
            uploadId: 'id',
            filename: 'f.txt',
            fileSize: 100,
            mimeType: 'text/plain',
            totalChunks: 1,
            chunkSize: 100,
            compression: false,
            parallel: 1,
        );

        $this->assertFalse($pending->compression);
    }

    #[Test]
    public function acceptsSingleChunk(): void
    {
        $pending = new PendingUpload(
            uploadId: 'id',
            filename: 'small.txt',
            fileSize: 50,
            mimeType: 'text/plain',
            totalChunks: 1,
            chunkSize: 5_242_880,
            compression: true,
            parallel: 1,
        );

        $this->assertSame(1, $pending->totalChunks);
        $this->assertSame(1, $pending->parallel);
    }

    #[Test]
    public function propertiesArePublic(): void
    {
        $reflection = new \ReflectionClass(PendingUpload::class);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        $names = array_map(static fn (\ReflectionProperty $p) => $p->getName(), $properties);

        $this->assertContains('uploadId', $names);
        $this->assertContains('filename', $names);
        $this->assertContains('fileSize', $names);
        $this->assertContains('mimeType', $names);
        $this->assertContains('totalChunks', $names);
        $this->assertContains('chunkSize', $names);
        $this->assertContains('compression', $names);
        $this->assertContains('parallel', $names);
    }
}
