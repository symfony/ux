<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Storage;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Upload\Exception\InvalidArgumentException;
use Symfony\UX\Upload\Exception\UploadSessionNotFoundException;
use Symfony\UX\Upload\Security\UploadContext;
use Symfony\UX\Upload\Storage\AbstractStorage;
use Symfony\UX\Upload\Storage\ChunkWriteResult;
use Symfony\UX\Upload\Storage\InMemoryStorage;
use Symfony\UX\Upload\Storage\PrunableStorageInterface;
use Symfony\UX\Upload\Storage\StorageInterface;

final class InMemoryStorageTest extends TestCase
{
    private InMemoryStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new InMemoryStorage();
    }

    #[Test]
    public function rejectsUnsafeCompletedPrefix(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new InMemoryStorage('/absolute');
    }

    public function testWriteAndRead(): void
    {
        $path = 'test.txt';
        $content = 'Hello World';

        $this->storage->write($path, $content);

        $this->assertTrue($this->storage->exists($path));
        $this->assertSame($content, $this->storage->read($path));
    }

    public function testDelete(): void
    {
        $path = 'test.txt';
        $this->storage->write($path, 'content');
        $this->assertTrue($this->storage->exists($path));

        $this->storage->delete($path);
        $this->assertFalse($this->storage->exists($path));
    }

    public function testChunkedUploadFlow(): void
    {
        $uploadId = 'upload-123';
        $metadata = [
            'filename' => 'test.txt',
            'fileSize' => 11,
            'totalChunks' => 2,
        ];

        $this->storage->initiate($uploadId, $metadata);

        $this->storage->storeChunk($uploadId, 0, 'Hello ', hash('sha256', 'Hello '));
        $this->storage->storeChunk($uploadId, 1, 'World', hash('sha256', 'World'));

        $chunks = $this->storage->listChunks($uploadId);
        $this->assertSame([0, 1], $chunks);

        $path = $this->storage->assemble($uploadId)->path;

        $this->assertStringContainsString('upload-123', $path);
        $this->assertTrue($this->storage->exists($path));
        $this->assertSame('Hello World', $this->storage->read($path));

        // assemble() no longer cleans up -- caller must call abort()
        $this->storage->abort($uploadId);
        $this->assertEmpty($this->storage->listChunks($uploadId));
    }

    // --- New tests below ---

    #[Test]
    public function implementsRequiredInterfaces(): void
    {
        $this->assertInstanceOf(StorageInterface::class, $this->storage);
        $this->assertInstanceOf(AbstractStorage::class, $this->storage);
        $this->assertInstanceOf(PrunableStorageInterface::class, $this->storage);
    }

    #[Test]
    public function isNotDistributed(): void
    {
        $this->assertFalse($this->storage->isDistributed());
    }

    #[Test]
    public function countsPendingSessionsByContext(): void
    {
        $this->storage->initiate('mine-1', ['filename' => 'a.txt', 'ownerId' => 'user-1']);
        $this->storage->initiate('mine-2', ['filename' => 'b.txt', 'ownerId' => 'user-1']);
        $this->storage->initiate('other-owner', ['filename' => 'c.txt', 'ownerId' => 'user-2']);
        $this->storage->initiate('other-tenant', ['filename' => 'd.txt', 'ownerId' => 'user-1', 'tenantId' => 'acme']);
        $this->storage->initiate('done', ['filename' => 'e.txt', 'ownerId' => 'user-1', 'completedPath' => '.tmp/completed/e.txt']);

        $this->assertSame(2, $this->storage->countPendingByContext(new UploadContext(ownerId: 'user-1')));
        $this->assertSame(1, $this->storage->countPendingByContext(new UploadContext(ownerId: 'user-1', tenantId: 'acme')));
        $this->assertSame(0, $this->storage->countPendingByContext(new UploadContext(ownerId: 'nobody')));
    }

    #[Test]
    public function readThrowsExceptionForNonExistentFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not found');

        $this->storage->read('nonexistent.txt');
    }

    #[Test]
    public function existsReturnsFalseForNonExistentFile(): void
    {
        $this->assertFalse($this->storage->exists('nonexistent.txt'));
    }

    #[Test]
    public function writeWithResourceStream(): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, 'stream content');
        rewind($stream);

        $this->storage->write('stream.txt', $stream);

        $this->assertTrue($this->storage->exists('stream.txt'));
        $this->assertSame('stream content', $this->storage->read('stream.txt'));

        fclose($stream);
    }

    #[Test]
    public function writeOverwritesExistingFile(): void
    {
        $this->storage->write('file.txt', 'first');
        $this->storage->write('file.txt', 'second');

        $this->assertSame('second', $this->storage->read('file.txt'));
    }

    #[Test]
    public function deleteNonExistentFileDoesNotThrow(): void
    {
        $this->storage->delete('nonexistent.txt');

        $this->assertFalse($this->storage->exists('nonexistent.txt'));
    }

    #[Test]
    public function getMetadataReturnsNullForUnknownUpload(): void
    {
        $this->assertNull($this->storage->getMetadata('unknown-id'));
    }

    #[Test]
    public function getMetadataReturnsStoredMetadata(): void
    {
        $metadata = [
            'filename' => 'doc.pdf',
            'fileSize' => 5000,
            'totalChunks' => 3,
            'mimeType' => 'application/pdf',
        ];

        $this->storage->initiate('upload-abc', $metadata);

        $this->assertSame($metadata, $this->storage->getMetadata('upload-abc'));
    }

    #[Test]
    public function storeChunkThrowsForUnknownSession(): void
    {
        $this->expectException(UploadSessionNotFoundException::class);

        $this->storage->storeChunk('unknown-id', 0, 'data', hash('sha256', 'data'));
    }

    #[Test]
    public function listChunksReturnsEmptyForUnknownUpload(): void
    {
        $this->assertSame([], $this->storage->listChunks('unknown-id'));
    }

    #[Test]
    public function listChunksReturnsSortedIndices(): void
    {
        $this->storage->initiate('upload-sort', ['filename' => 'f.txt', 'totalChunks' => 4]);

        // Store chunks out of order
        $this->storage->storeChunk('upload-sort', 3, 'D', hash('sha256', 'D'));
        $this->storage->storeChunk('upload-sort', 0, 'A', hash('sha256', 'A'));
        $this->storage->storeChunk('upload-sort', 2, 'C', hash('sha256', 'C'));
        $this->storage->storeChunk('upload-sort', 1, 'B', hash('sha256', 'B'));

        $this->assertSame([0, 1, 2, 3], $this->storage->listChunks('upload-sort'));
    }

    #[Test]
    public function storeChunkIsIdempotentForIdenticalRetries(): void
    {
        $this->storage->initiate('upload-ow', ['filename' => 'f.txt', 'totalChunks' => 1]);

        self::assertSame(ChunkWriteResult::Stored, $this->storage->storeChunk('upload-ow', 0, 'first', hash('sha256', 'first')));
        self::assertSame(ChunkWriteResult::AlreadyPresent, $this->storage->storeChunk('upload-ow', 0, 'first', hash('sha256', 'first')));

        $this->assertSame([0], $this->storage->listChunks('upload-ow'));
    }

    #[Test]
    public function storeChunkRejectsOverwriteWithDifferentContent(): void
    {
        $this->storage->initiate('upload-ow2', ['filename' => 'f.txt', 'totalChunks' => 1]);
        $this->storage->storeChunk('upload-ow2', 0, 'first', hash('sha256', 'first'));

        $this->expectException(InvalidArgumentException::class);

        $this->storage->storeChunk('upload-ow2', 0, 'second', hash('sha256', 'second'));
    }

    #[Test]
    public function assembleThrowsForUnknownSession(): void
    {
        $this->expectException(UploadSessionNotFoundException::class);

        $this->storage->assemble('unknown-id');
    }

    #[Test]
    public function assembleThrowsForMissingChunk(): void
    {
        $this->storage->initiate('upload-miss', [
            'filename' => 'file.txt',
            'totalChunks' => 3,
        ]);

        $this->storage->storeChunk('upload-miss', 0, 'A', hash('sha256', 'A'));
        // Chunk 1 is missing
        $this->storage->storeChunk('upload-miss', 2, 'C', hash('sha256', 'C'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing chunk: 1');

        $this->storage->assemble('upload-miss');
    }

    #[Test]
    public function assembleCleansUpMetadataAndChunks(): void
    {
        $this->storage->initiate('upload-clean', [
            'filename' => 'file.txt',
            'totalChunks' => 1,
        ]);
        $this->storage->storeChunk('upload-clean', 0, 'data', hash('sha256', 'data'));

        $this->storage->assemble('upload-clean');

        // After assemble(), metadata is still available (needed for validation)
        $this->assertNotNull($this->storage->getMetadata('upload-clean'));

        // Cleanup happens via abort()
        $this->storage->abort('upload-clean');
        $this->assertNull($this->storage->getMetadata('upload-clean'));
        $this->assertSame([], $this->storage->listChunks('upload-clean'));
    }

    #[Test]
    public function assembleGeneratesPathWithExtension(): void
    {
        $this->storage->initiate('upload-ext', [
            'filename' => 'photo.jpg',
            'totalChunks' => 1,
        ]);
        $this->storage->storeChunk('upload-ext', 0, 'image-data', hash('sha256', 'image-data'));

        $path = $this->storage->assemble('upload-ext')->path;

        $this->assertStringEndsWith('.jpg', $path);
        $this->assertStringContainsString('upload-ext', $path);
    }

    #[Test]
    public function assembleGeneratesPathWithoutExtensionForEmptyFilename(): void
    {
        $this->storage->initiate('upload-noext', [
            'filename' => '',
            'totalChunks' => 1,
        ]);
        $this->storage->storeChunk('upload-noext', 0, 'data', hash('sha256', 'data'));

        $path = $this->storage->assemble('upload-noext')->path;

        $this->assertStringContainsString('upload-noext', $path);
        // No trailing dot
        $this->assertStringEndsNotWith('.', $path);
    }

    #[Test]
    public function abortRemovesMetadataAndChunks(): void
    {
        $this->storage->initiate('upload-abort', [
            'filename' => 'file.txt',
            'totalChunks' => 2,
        ]);
        $this->storage->storeChunk('upload-abort', 0, 'A', hash('sha256', 'A'));
        $this->storage->storeChunk('upload-abort', 1, 'B', hash('sha256', 'B'));

        $this->storage->abort('upload-abort');

        $this->assertNull($this->storage->getMetadata('upload-abort'));
        $this->assertSame([], $this->storage->listChunks('upload-abort'));
    }

    #[Test]
    public function abortOnUnknownUploadDoesNotThrow(): void
    {
        // Should not throw, just silently do nothing
        $this->storage->abort('unknown-id');

        $this->assertNull($this->storage->getMetadata('unknown-id'));
    }

    #[Test]
    public function completeSessionKeepsMetadataAndRemovesChunks(): void
    {
        $this->storage->initiate('completed', ['filename' => 'file.txt', 'totalChunks' => 1]);
        $this->storage->storeChunk('completed', 0, 'data', hash('sha256', 'data'));
        $metadata = ['filename' => 'file.txt', 'completedPath' => '.tmp/completed/file.txt'];

        $this->storage->completeSession('completed', $metadata);

        self::assertSame($metadata, $this->storage->getMetadata('completed'));
        self::assertSame([], $this->storage->listChunks('completed'));
    }

    #[Test]
    public function assemblingAnExistingCompletedPathIsIdempotent(): void
    {
        $this->storage->initiate('stable', ['filename' => 'file.txt', 'totalChunks' => 1]);
        $this->storage->storeChunk('stable', 0, 'data', hash('sha256', 'data'));
        $expiresAt = time() + 3600;

        $first = $this->storage->assemble('stable', expiresAt: $expiresAt)->path;
        $second = $this->storage->assemble('stable', expiresAt: $expiresAt)->path;

        self::assertSame($first, $second);
        self::assertSame('data', $this->storage->read($second));
    }

    #[Test]
    public function pruneRemovesOldUploads(): void
    {
        // Create an old upload (2 hours ago)
        $this->storage->initiate('old-upload', [
            'filename' => 'old.txt',
            'totalChunks' => 1,
            'createdAt' => time() - 7200,
        ]);
        $this->storage->storeChunk('old-upload', 0, 'old data', hash('sha256', 'old data'));

        // Create a recent upload (5 minutes ago)
        $this->storage->initiate('new-upload', [
            'filename' => 'new.txt',
            'totalChunks' => 1,
            'createdAt' => time() - 300,
        ]);
        $this->storage->storeChunk('new-upload', 0, 'new data', hash('sha256', 'new data'));

        // Prune uploads older than 1 hour
        $this->storage->prune(3600);

        $this->assertNull($this->storage->getMetadata('old-upload'));
        $this->assertSame([], $this->storage->listChunks('old-upload'));

        $this->assertNotNull($this->storage->getMetadata('new-upload'));
        $this->assertSame([0], $this->storage->listChunks('new-upload'));
    }

    #[Test]
    public function pruneRemovesExpiredCompletedUploadAndFile(): void
    {
        $path = '.tmp/completed/'.(time() + 3600).'-0123456789abcdef0123456789abcdef.txt';
        $this->storage->write($path, 'data');
        $this->storage->initiate('completed', [
            'filename' => 'file.txt',
            'completedPath' => $path,
            'expiresAt' => time() - 1,
            'createdAt' => time() - 3600,
        ]);

        $this->storage->prune(3600);

        self::assertFalse($this->storage->exists($path));
        self::assertNull($this->storage->getMetadata('completed'));
    }

    #[Test]
    public function pruneHandlesMissingCreatedAt(): void
    {
        // Upload without createdAt defaults to 0 (epoch), so it's always old
        $this->storage->initiate('no-timestamp', [
            'filename' => 'file.txt',
            'totalChunks' => 1,
        ]);
        $this->storage->storeChunk('no-timestamp', 0, 'data', hash('sha256', 'data'));

        $this->storage->prune(3600);

        $this->assertNull($this->storage->getMetadata('no-timestamp'));
    }

    #[Test]
    public function pruneWithZeroMaxAgeRemovesOlderUploads(): void
    {
        // createdAt one second in the past ensures it is strictly less than cutoff
        $this->storage->initiate('recent', [
            'filename' => 'file.txt',
            'totalChunks' => 1,
            'createdAt' => time() - 1,
        ]);
        $this->storage->storeChunk('recent', 0, 'data', hash('sha256', 'data'));

        // maxAge=0 means cutoff = current time; uploads created before now are pruned
        $this->storage->prune(0);

        $this->assertNull($this->storage->getMetadata('recent'));
    }

    #[Test]
    public function pruneDeletesOnlyExpiredGeneratedCompletedFiles(): void
    {
        $expired = '.tmp/completed/'.(time() - 1).'-0123456789abcdef0123456789abcdef.txt';
        $fresh = '.tmp/completed/'.(time() + 3600).'-fedcba9876543210fedcba9876543210.txt';
        $applicationFile = 'documents/keep.txt';
        foreach ([$expired, $fresh, $applicationFile] as $path) {
            $this->storage->write($path, 'data');
        }

        $this->storage->prune(3600);

        self::assertFalse($this->storage->exists($expired));
        self::assertTrue($this->storage->exists($fresh));
        self::assertTrue($this->storage->exists($applicationFile));
    }

    #[Test]
    public function multipleUploadsAreIsolated(): void
    {
        $this->storage->initiate('upload-a', [
            'filename' => 'a.txt',
            'totalChunks' => 1,
        ]);
        $this->storage->initiate('upload-b', [
            'filename' => 'b.txt',
            'totalChunks' => 1,
        ]);

        $this->storage->storeChunk('upload-a', 0, 'data-A', hash('sha256', 'data-A'));
        $this->storage->storeChunk('upload-b', 0, 'data-B', hash('sha256', 'data-B'));

        // Abort one, the other should survive
        $this->storage->abort('upload-a');

        $this->assertNull($this->storage->getMetadata('upload-a'));
        $this->assertNotNull($this->storage->getMetadata('upload-b'));
        $this->assertSame([0], $this->storage->listChunks('upload-b'));
    }

    #[Test]
    public function assembleWithZeroTotalChunksProducesEmptyFile(): void
    {
        $this->storage->initiate('upload-zero', [
            'filename' => 'empty.txt',
            'totalChunks' => 0,
        ]);

        $path = $this->storage->assemble('upload-zero')->path;

        $this->assertTrue($this->storage->exists($path));
        $this->assertSame('', $this->storage->read($path));
    }
}
