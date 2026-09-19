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

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\UX\Upload\Exception\InvalidArgumentException;
use Symfony\UX\Upload\Exception\StorageException;
use Symfony\UX\Upload\Security\UploadContext;
use Symfony\UX\Upload\Storage\ChunkWriteResult;
use Symfony\UX\Upload\Storage\LocalStorage;
use Symfony\UX\Upload\Tests\NativeFunctions;

final class LocalStorageTest extends TestCase
{
    private string $tempDir;
    private LocalStorage $storage;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/ux_upload_test_'.uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tempDir);

        $this->storage = new LocalStorage($this->tempDir, $this->tempDir.'/.tmp', $this->filesystem);
    }

    protected function tearDown(): void
    {
        NativeFunctions::reset();
        if (is_dir($this->tempDir)) {
            $this->filesystem->remove($this->tempDir);
        }
    }

    public function testWriteAndRead()
    {
        $path = 'test.txt';
        $content = 'Hello World';

        $this->storage->write($path, $content);

        $this->assertTrue($this->storage->exists($path));

        $stream = $this->storage->read($path);
        $this->assertIsResource($stream);
        $this->assertSame($content, stream_get_contents($stream));
        fclose($stream);
    }

    public function testIsNotDistributed()
    {
        self::assertFalse($this->storage->isDistributed());
    }

    public function testDelete()
    {
        $path = 'test.txt';
        $this->storage->write($path, 'content');
        $this->assertTrue($this->storage->exists($path));

        $this->storage->delete($path);
        $this->assertFalse($this->storage->exists($path));
    }

    public function testChunkedUploadFlow()
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

        $stream = $this->storage->read($path);
        $this->assertIsResource($stream);
        $this->assertSame('Hello World', stream_get_contents($stream));
        fclose($stream);

        // assemble() no longer cleans up -- caller must call abort()
        $this->storage->abort($uploadId);
        $this->assertEmpty($this->storage->listChunks($uploadId));
    }

    public function testAtomicChunkWriteIsIdempotentAndRejectsDivergentContent()
    {
        $this->storage->initiate('atomic', ['filename' => 'file.txt', 'fileSize' => 4, 'totalChunks' => 1]);

        self::assertSame(ChunkWriteResult::Stored, $this->storage->storeChunk('atomic', 0, 'data', hash('sha256', 'data')));
        self::assertSame(ChunkWriteResult::AlreadyPresent, $this->storage->storeChunk('atomic', 0, 'data', hash('sha256', 'data')));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('different content');
        $this->storage->storeChunk('atomic', 0, 'evil', hash('sha256', 'evil'));
    }

    public function testCountsPendingUploadsByOwner()
    {
        $this->storage->initiate('owned-1', ['ownerId' => 'one']);
        $this->storage->initiate('owned-2', ['ownerId' => 'one']);
        $this->storage->initiate('owned-3', ['ownerId' => 'two']);
        $this->storage->completeSession('owned-1', [
            'ownerId' => 'one',
            'completedPath' => '.tmp/completed/2000000000-0123456789abcdef0123456789abcdef.txt',
        ]);

        self::assertSame(1, $this->storage->countPendingByContext(new UploadContext('one')));
        self::assertSame(1, $this->storage->countPendingByContext(new UploadContext('two')));
        self::assertSame(0, $this->storage->countPendingByContext(new UploadContext()));
    }

    public function testPruneDropsPendingMarkersLeftWithoutASession()
    {
        $context = new UploadContext('ghost');
        $marker = $this->tempDir.'/.tmp/.pending/'.$context->fingerprint().'/vanished';
        $this->filesystem->dumpFile($marker, '');

        // A crash between the two writes would otherwise hold a quota slot forever.
        self::assertSame(1, $this->storage->countPendingByContext($context));

        $this->storage->prune(3600);

        self::assertSame(0, $this->storage->countPendingByContext($context));
        self::assertFileDoesNotExist($marker);
    }

    public function testGetUploadDirThrowsForInvalidId()
    {
        $reflection = new \ReflectionClass(LocalStorage::class);
        $method = $reflection->getMethod('getUploadDir');

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Invalid upload ID');
        $method->invoke($this->storage, '../invalid');
    }

    public function testRejectsPathTraversalInUploadId()
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Invalid upload ID');

        $this->storage->initiate('../etc/passwd', ['filename' => 'test.txt', 'totalChunks' => 1]);
    }

    public function testRejectsSlashInUploadId()
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Invalid upload ID');

        $this->storage->listChunks('foo/bar');
    }

    public function testAcceptsValidUploadId()
    {
        // 32-char lowercase hex, the format produced by Uploader::initializeUpload().
        $uploadId = bin2hex(random_bytes(16));
        $this->storage->initiate($uploadId, [
            'filename' => 'test.txt',
            'fileSize' => 100,
            'totalChunks' => 1,
        ]);

        $this->assertNotNull($this->storage->getMetadata($uploadId));
    }

    public function testRejectsDotsInUploadId()
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Invalid upload ID');

        $this->storage->initiate('upload.123', ['filename' => 'test.txt', 'totalChunks' => 1]);
    }

    public function testReadThrowsOnMissingFile()
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('not found');

        $this->storage->read('nonexistent.txt');
    }

    public function testReadThrowsWhenFileCannotBeOpened()
    {
        if ('\\' === \DIRECTORY_SEPARATOR) {
            self::markTestSkipped('chmod() does not restrict read access on Windows.');
        }

        $path = 'unreadable.txt';
        $this->storage->write($path, 'content');

        $fullPath = $this->tempDir.'/'.$path;
        chmod($fullPath, 0o000);

        try {
            $this->storage->read($path);
            $this->fail('Expected StorageException');
        } catch (StorageException $e) {
            $this->assertStringContainsString('Cannot open file for reading', $e->getMessage());
        } finally {
            chmod($fullPath, 0o644);
        }
    }

    public function testPruneDeletesStaleUploads()
    {
        $uploadId = 'stale-upload';
        $this->storage->initiate($uploadId, [
            'filename' => 'test.txt',
            'fileSize' => 100,
            'totalChunks' => 1,
            'createdAt' => time() - 7200,
        ]);

        self::assertNotNull($this->storage->getMetadata($uploadId));

        $this->storage->prune(3600);

        self::assertNull($this->storage->getMetadata($uploadId));
    }

    public function testPruneKeepsFreshUploads()
    {
        $uploadId = 'fresh-upload';
        $this->storage->initiate($uploadId, [
            'filename' => 'test.txt',
            'fileSize' => 100,
            'totalChunks' => 1,
            'createdAt' => time() - 60,
        ]);

        $this->storage->prune(3600);

        self::assertNotNull($this->storage->getMetadata($uploadId));
    }

    public function testPruneDeletesOnlyExpiredGeneratedCompletedFiles()
    {
        $expired = '.tmp/completed/'.(time() - 1).'-0123456789abcdef0123456789abcdef.txt';
        $fresh = '.tmp/completed/'.(time() + 3600).'-fedcba9876543210fedcba9876543210.txt';
        $applicationFile = 'documents/keep.txt';
        $unrecognized = '.tmp/completed/not-a-generated-key.txt';
        foreach ([$expired, $fresh, $applicationFile, $unrecognized] as $path) {
            $this->storage->write($path, 'data');
        }

        $this->storage->prune(3600);

        self::assertFalse($this->storage->exists($expired));
        self::assertTrue($this->storage->exists($fresh));
        self::assertTrue($this->storage->exists($applicationFile));
        self::assertTrue($this->storage->exists($unrecognized));
    }

    public function testPruneDeletesUploadWithMissingMetadata()
    {
        // Create an upload directory without metadata.json
        $uploadDir = $this->tempDir.'/.tmp/orphan-upload';
        $this->filesystem->mkdir($uploadDir);
        file_put_contents($uploadDir.'/chunk_0', 'data');

        self::assertDirectoryExists($uploadDir);

        $this->storage->prune(3600);

        self::assertDirectoryDoesNotExist($uploadDir);
    }

    public function testPruneDeletesUploadWithInvalidJson()
    {
        $uploadDir = $this->tempDir.'/.tmp/corrupt-upload';
        $this->filesystem->mkdir($uploadDir);
        file_put_contents($uploadDir.'/metadata.json', '{invalid json');

        self::assertDirectoryExists($uploadDir);

        $this->storage->prune(3600);

        self::assertDirectoryDoesNotExist($uploadDir);
    }

    public function testPruneNoOpWhenTempDirMissing()
    {
        // Remove the temp dir completely
        $tempDir = $this->tempDir.'/.tmp';
        if (is_dir($tempDir)) {
            $this->filesystem->remove($tempDir);
        }

        // Should not throw
        $this->storage->prune(3600);

        self::assertTrue(true);
    }

    public function testGetMetadataReturnsNullForMissingSession()
    {
        self::assertNull($this->storage->getMetadata('nonexistent'));
    }

    public function testRejectsScalarAndListMetadata()
    {
        foreach (['true', '["value"]'] as $index => $json) {
            $uploadId = 'invalid-metadata-'.$index;
            $directory = $this->tempDir.'/.tmp/'.$uploadId;
            $this->filesystem->mkdir($directory);
            file_put_contents($directory.'/metadata.json', $json);

            try {
                $this->storage->getMetadata($uploadId);
                self::fail('Invalid metadata must be rejected.');
            } catch (StorageException $exception) {
                self::assertStringContainsString('Invalid upload metadata', $exception->getMessage());
            }
        }
    }

    public function testPendingCountIgnoresCorruptMetadata()
    {
        $directory = $this->tempDir.'/.tmp/corrupt-count';
        $this->filesystem->mkdir($directory);
        file_put_contents($directory.'/metadata.json', '{invalid');

        self::assertSame(0, $this->storage->countPendingByContext(new UploadContext()));
    }

    public function testListChunksReturnsEmptyForMissingDir()
    {
        self::assertSame([], $this->storage->listChunks('nonexistent'));
    }

    public function testGetDirectoryReturnsConfiguredPath()
    {
        self::assertSame($this->tempDir, $this->storage->getDirectory());
    }

    public function testAssemblePreservesFileExtension()
    {
        $uploadId = 'ext-test';
        $this->storage->initiate($uploadId, [
            'filename' => 'report.pdf',
            'fileSize' => 7,
            'totalChunks' => 1,
        ]);
        $this->storage->storeChunk($uploadId, 0, 'content', hash('sha256', 'content'));

        $path = $this->storage->assemble($uploadId)->path;

        self::assertStringEndsWith('.pdf', $path);
    }

    public function testAssembleWithMultipleChunks()
    {
        $uploadId = 'multi-chunk';
        $this->storage->initiate($uploadId, [
            'filename' => 'test.txt',
            'fileSize' => 11,
            'totalChunks' => 3,
        ]);
        $this->storage->storeChunk($uploadId, 0, 'AAA', hash('sha256', 'AAA'));
        $this->storage->storeChunk($uploadId, 1, 'BBB', hash('sha256', 'BBB'));
        $this->storage->storeChunk($uploadId, 2, 'CC', hash('sha256', 'CC'));

        $path = $this->storage->assemble($uploadId)->path;

        $stream = $this->storage->read($path);
        self::assertSame('AAABBBCC', stream_get_contents($stream));
        fclose($stream);
    }

    public function testVerifiedAssemblyMeasuresAndHashesDuringCopy()
    {
        $uploadId = 'verified';
        $this->storage->initiate($uploadId, [
            'filename' => 'verified.txt',
            'fileSize' => 11,
            'totalChunks' => 2,
        ]);
        $this->storage->storeChunk($uploadId, 0, 'Hello ', hash('sha256', 'Hello '));
        $this->storage->storeChunk($uploadId, 1, 'World', hash('sha256', 'World'));

        $assembled = $this->storage->assemble($uploadId, 'sha256');

        self::assertSame(11, $assembled->size);
        self::assertSame(hash('sha256', 'Hello World'), $assembled->hash);
        self::assertTrue($this->storage->exists($assembled->path));
    }

    public function testVerifiedAssemblyWithMissingSessionThrows()
    {
        $this->expectException(\Symfony\UX\Upload\Exception\UploadSessionNotFoundException::class);

        $this->storage->assemble('missing');
    }

    public function testAssemblyReplacesAnUncommittedObjectLeftByACrash()
    {
        $uploadId = str_repeat('a', 32);
        $expiresAt = time() + 3600;
        $this->storage->initiate($uploadId, [
            'filename' => 'verified.txt',
            'fileSize' => 4,
            'totalChunks' => 1,
            'hash' => hash('sha256', 'good'),
        ]);
        $this->storage->storeChunk($uploadId, 0, 'good', hash('sha256', 'good'));
        $path = \sprintf('.tmp/completed/%d-%s.txt', $expiresAt, $uploadId);
        $this->storage->write($path, 'x');

        $assembled = $this->storage->assemble($uploadId, 'sha256', $expiresAt);
        $stream = $this->storage->read($assembled->path);
        try {
            self::assertSame('good', stream_get_contents($stream));
        } finally {
            fclose($stream);
        }
        self::assertSame(4, $assembled->size);
        self::assertSame(hash('sha256', 'good'), $assembled->hash);
    }

    public function testPruneSkipsAnUploadWhoseLifecycleLockIsHeld()
    {
        $lockFactory = new LockFactory(new FlockStore());
        $storage = new LocalStorage($this->tempDir, $this->tempDir.'/.tmp-lock', $this->filesystem, lockFactory: $lockFactory);
        $uploadId = 'active-upload';
        $storage->initiate($uploadId, [
            'filename' => 'active.txt',
            'createdAt' => time() - 7200,
            'totalChunks' => 1,
        ]);
        $lock = $lockFactory->createLock('ux_upload_lifecycle_'.$uploadId);
        self::assertTrue($lock->acquire());

        $storage->prune(3600);
        self::assertNotNull($storage->getMetadata($uploadId));

        $lock->release();
        $storage->prune(3600);
        self::assertNull($storage->getMetadata($uploadId));
    }

    public function testPruneDeletesExpiredCompletedSessionAndObject()
    {
        $uploadId = 'expired-completed';
        $path = '.tmp/completed/'.(time() + 3600).'-0123456789abcdef0123456789abcdef.txt';
        $this->storage->write($path, 'data');
        $this->storage->initiate($uploadId, [
            'expiresAt' => time() - 1,
            'completedPath' => $path,
        ]);

        $this->storage->prune(3600);

        self::assertFalse($this->storage->exists($path));
        self::assertNull($this->storage->getMetadata($uploadId));
    }

    public function testAssemblyWrapsPublicationFailureAndRemovesPartialFile()
    {
        $filesystem = $this->getMockBuilder(Filesystem::class)->onlyMethods(['rename'])->getMock();
        $filesystem->expects(self::atLeastOnce())->method('rename')->willReturnCallback(static function (string $origin, string $target, bool $overwrite = false): void {
            // tempnam() only keeps the first three characters of the prefix on
            // Windows, so the partial file is matched on those.
            if (str_starts_with(basename($origin), '.ux')) {
                throw new IOException('rename failed');
            }
            if (!$overwrite && file_exists($target)) {
                throw new IOException('Target already exists.');
            }
            if (!rename($origin, $target)) {
                throw new IOException('Fallback rename failed.');
            }
        });
        $storage = new LocalStorage($this->tempDir, $this->tempDir.'/.tmp-publish', $filesystem);
        $storage->initiate('publish-failure', ['filename' => 'file.txt', 'totalChunks' => 1]);
        $storage->storeChunk('publish-failure', 0, 'data', hash('sha256', 'data'));

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Cannot publish assembled upload');

        $storage->assemble('publish-failure');
    }

    public function testAssembleThrowsWhenFileCannotBeOpenedForWriting()
    {
        $uploadId = 'fail-id';
        $this->storage->initiate($uploadId, ['filename' => 'f', 'totalChunks' => 1]);
        $this->storage->storeChunk($uploadId, 0, 'data', hash('sha256', 'data'));
        NativeFunctions::mock('Symfony\\UX\\Upload\\Storage\\fopen', false);

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Cannot open file for writing');

        $this->storage->assemble($uploadId);
    }

    public function testAtomicChunkWriteReportsNativeWriteFailure()
    {
        $this->storage->initiate('write-failure', ['filename' => 'f', 'totalChunks' => 1]);
        NativeFunctions::mock('Symfony\\UX\\Upload\\Storage\\fwrite', false);

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Unable to write chunk 0');

        $this->storage->storeChunk('write-failure', 0, 'data', hash('sha256', 'data'));
    }

    public function testAssemblyReportsTemporaryFileCreationFailure()
    {
        $this->prepareAssembly('tempnam-failure');
        NativeFunctions::mock('Symfony\\UX\\Upload\\Storage\\tempnam', false);

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Cannot create temporary assembly file');

        $this->storage->assemble('tempnam-failure');
    }

    public function testAssemblyReportsChunkOpenFailure()
    {
        $this->prepareAssembly('chunk-open-failure');
        NativeFunctions::mock(
            'Symfony\\UX\\Upload\\Storage\\fopen',
            NativeFunctions::PASSTHROUGH,
            false,
        );

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Cannot open chunk: 0');

        $this->storage->assemble('chunk-open-failure');
    }

    public function testAssemblyReportsChunkReadFailure()
    {
        $this->prepareAssembly('chunk-read-failure');
        NativeFunctions::mock('Symfony\\UX\\Upload\\Storage\\fread', false);

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Cannot read chunk: 0');

        $this->storage->assemble('chunk-read-failure');
    }

    public function testAssemblyIgnoresAnEmptyIntermediateRead()
    {
        $this->prepareAssembly('empty-read');
        NativeFunctions::mock(
            'Symfony\\UX\\Upload\\Storage\\fread',
            '',
            NativeFunctions::PASSTHROUGH,
        );

        self::assertStringContainsString('empty-read', $this->storage->assemble('empty-read')->path);
    }

    public function testAssemblyReportsNativeWriteFailure()
    {
        $this->prepareAssembly('assembly-write-failure');
        NativeFunctions::mock('Symfony\\UX\\Upload\\Storage\\fwrite', 0);

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Cannot write assembled upload');

        $this->storage->assemble('assembly-write-failure');
    }

    public function testAssembleThrowsOnMissingChunk()
    {
        $uploadId = 'missing-chunk';
        $this->storage->initiate($uploadId, [
            'filename' => 'test.txt',
            'fileSize' => 10,
            'totalChunks' => 2,
        ]);
        $this->storage->storeChunk($uploadId, 0, 'data', hash('sha256', 'data'));
        // Chunk 1 is missing

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Missing chunk: 1');

        $this->storage->assemble($uploadId);
    }

    private function prepareAssembly(string $uploadId): void
    {
        $this->storage->initiate($uploadId, [
            'filename' => 'test.txt',
            'fileSize' => 4,
            'totalChunks' => 1,
        ]);
        $this->storage->storeChunk($uploadId, 0, 'data', hash('sha256', 'data'));
    }
}
