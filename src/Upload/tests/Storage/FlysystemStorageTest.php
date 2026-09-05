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

use League\Flysystem\DirectoryListing;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;
use Symfony\UX\Upload\Exception\InvalidArgumentException;
use Symfony\UX\Upload\Exception\StorageException;
use Symfony\UX\Upload\Exception\UploadSessionNotFoundException;
use Symfony\UX\Upload\Security\UploadContext;
use Symfony\UX\Upload\Storage\ChunkWriteResult;
use Symfony\UX\Upload\Storage\FlysystemStorage;
use Symfony\UX\Upload\Tests\NativeFunctions;

final class FlysystemStorageTest extends TestCase
{
    private FlysystemStorage $storage;
    private FilesystemOperator $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = $this->createStub(FilesystemOperator::class);
        $this->storage = new FlysystemStorage($this->filesystem);
    }

    protected function tearDown(): void
    {
        NativeFunctions::reset();
    }

    /**
     * Creates a mock FilesystemOperator for tests that need ->expects().
     *
     * @return array{FlysystemStorage, FilesystemOperator&\PHPUnit\Framework\MockObject\MockObject}
     */
    private function createMockFilesystem(): array
    {
        $filesystem = $this->createMock(FilesystemOperator::class);

        return [new FlysystemStorage($filesystem), $filesystem];
    }

    #[Test]
    public function isDistributed(): void
    {
        self::assertTrue($this->storage->isDistributed());
    }

    #[Test]
    public function writeString(): void
    {
        [$storage, $filesystem] = $this->createMockFilesystem();

        $filesystem->expects($this->once())
            ->method('write')
            ->with('test.txt', 'content');

        $storage->write('test.txt', 'content');
    }

    #[Test]
    public function writeResource(): void
    {
        [$storage, $filesystem] = $this->createMockFilesystem();

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, 'stream-content');
        rewind($stream);

        $filesystem->expects($this->once())
            ->method('writeStream')
            ->with('test.txt', $stream);

        $storage->write('test.txt', $stream);

        fclose($stream);
    }

    #[Test]
    public function read(): void
    {
        [$storage, $filesystem] = $this->createMockFilesystem();

        $stream = fopen('php://memory', 'r');

        $filesystem->expects($this->once())
            ->method('readStream')
            ->with('test.txt')
            ->willReturn($stream);

        $this->assertSame($stream, $storage->read('test.txt'));
    }

    #[Test]
    public function deleteWhenFileExists(): void
    {
        [$storage, $filesystem] = $this->createMockFilesystem();

        $filesystem->method('fileExists')
            ->with('test.txt')
            ->willReturn(true);

        $filesystem->expects($this->once())
            ->method('delete')
            ->with('test.txt');

        $storage->delete('test.txt');
    }

    #[Test]
    public function deleteWhenFileDoesNotExist(): void
    {
        [$storage, $filesystem] = $this->createMockFilesystem();

        $filesystem->method('fileExists')
            ->with('missing.txt')
            ->willReturn(false);

        $filesystem->expects($this->never())
            ->method('delete');

        $storage->delete('missing.txt');
    }

    #[Test]
    public function existsDelegatesToFileExists(): void
    {
        [$storage, $filesystem] = $this->createMockFilesystem();

        $filesystem->expects($this->exactly(2))
            ->method('fileExists')
            ->willReturnMap([
                ['found.txt', true],
                ['missing.txt', false],
            ]);

        $this->assertTrue($storage->exists('found.txt'));
        $this->assertFalse($storage->exists('missing.txt'));
    }

    #[Test]
    public function initiate(): void
    {
        [$storage, $filesystem] = $this->createMockFilesystem();

        $metadata = ['filename' => 'test.txt'];

        $writes = [];
        $filesystem->expects($this->exactly(2))
            ->method('write')
            ->willReturnCallback(static function (string $path, string $contents) use (&$writes): void {
                $writes[$path] = $contents;
            });

        $storage->initiate('abc123', $metadata);

        self::assertSame(json_encode(['filename' => 'test.txt']), $writes['.tmp/abc123/metadata.json'] ?? null);
        // The session is also indexed so it can be counted without being read.
        self::assertArrayHasKey('.tmp/.pending/'.new UploadContext()->fingerprint().'/abc123', $writes);
    }

    #[Test]
    public function listChunksNoMetadata(): void
    {
        $this->filesystem->method('fileExists')
            ->willReturn(false);

        $this->assertSame([], $this->storage->listChunks('abc123'));
    }

    #[Test]
    public function listChunksWithChunks(): void
    {
        $this->filesystem->method('fileExists')
            ->willReturn(true);

        $items = [];
        foreach ([2, 0, 1] as $index) {
            $item = $this->createStub(\League\Flysystem\StorageAttributes::class);
            $item->method('path')->willReturn('.tmp/abc123/chunk_'.$index);
            $items[] = $item;
        }
        $this->filesystem->method('read')->willReturn('{}');
        $this->filesystem->method('listContents')->willReturn(new DirectoryListing($items));

        $this->assertSame([0, 1, 2], $this->storage->listChunks('abc123'));
    }

    #[Test]
    public function listChunksFiltersNonChunkFiles(): void
    {
        $this->filesystem->method('fileExists')
            ->willReturn(true);

        $chunk = $this->createStub(\League\Flysystem\StorageAttributes::class);
        $chunk->method('path')->willReturn('.tmp/abc123/chunk_0');
        $metadata = $this->createStub(\League\Flysystem\StorageAttributes::class);
        $metadata->method('path')->willReturn('.tmp/abc123/metadata.json');
        $this->filesystem->method('read')->willReturn('{}');
        $this->filesystem->method('listContents')->willReturn(new DirectoryListing([$metadata, $chunk]));

        $this->assertSame([0], $this->storage->listChunks('abc123'));
    }

    #[Test]
    public function getMetadataFileNotFound(): void
    {
        $this->filesystem->method('fileExists')
            ->willReturn(false);

        $this->assertNull($this->storage->getMetadata('abc123'));
    }

    #[Test]
    public function getMetadataWithValidJson(): void
    {
        $metadata = ['filename' => 'test.txt', 'totalChunks' => 3];

        $this->filesystem->method('fileExists')
            ->willReturn(true);

        $this->filesystem->method('read')
            ->willReturn(json_encode($metadata));

        $this->assertSame($metadata, $this->storage->getMetadata('abc123'));
    }

    #[Test]
    public function abort(): void
    {
        [$storage, $filesystem] = $this->createMockFilesystem();

        $filesystem->expects($this->once())
            ->method('deleteDirectory')
            ->with('.tmp/abc123');

        $storage->abort('abc123');
    }

    #[Test]
    public function storeChunkWritesToCorrectPath(): void
    {
        [$storage, $filesystem] = $this->createMockFilesystem();

        // storeChunk calls assertSessionExists first, so metadata must exist;
        // the chunk path is then probed for the duplicate check.
        $metadata = ['filename' => 'test.txt'];
        $filesystem->method('fileExists')->willReturnMap([
            ['.tmp/abc123/metadata.json', true],
            ['.tmp/abc123/chunk_2', false],
        ]);

        $filesystem->method('read')
            ->with('.tmp/abc123/metadata.json')
            ->willReturn(json_encode($metadata));

        $filesystem->expects($this->once())
            ->method('write')
            ->with('.tmp/abc123/chunk_2', 'chunk-data');

        $storage->storeChunk('abc123', 2, 'chunk-data', hash('sha256', 'chunk-data'));
    }

    #[Test]
    public function storeChunkThrowsWhenSessionMissing(): void
    {
        $this->filesystem->method('fileExists')
            ->willReturn(false);

        $this->expectException(UploadSessionNotFoundException::class);

        $this->storage->storeChunk('unknown', 0, 'data', hash('sha256', 'data'));
    }

    #[Test]
    public function atomicChunkRetryAcceptsTheSameContent(): void
    {
        [$storage, $filesystem] = $this->createMockFilesystem();
        $filesystem->method('fileExists')->willReturnMap([
            ['.tmp/abc123/metadata.json', true],
            ['.tmp/abc123/chunk_0', true],
        ]);
        $filesystem->method('read')->willReturn('{}');
        $filesystem->method('readStream')
            ->with('.tmp/abc123/chunk_0')
            ->willReturnCallback(static function () {
                $stream = fopen('php://memory', 'r+');
                fwrite($stream, 'same-content');
                rewind($stream);

                return $stream;
            });
        $filesystem->expects(self::never())->method('write');

        self::assertSame(
            ChunkWriteResult::AlreadyPresent,
            $storage->storeChunk('abc123', 0, 'same-content', hash('sha256', 'same-content')),
        );
    }

    #[Test]
    public function atomicChunkRetryRejectsDifferentContent(): void
    {
        $filesystem = $this->createStub(FilesystemOperator::class);
        $storage = new FlysystemStorage($filesystem);
        $filesystem->method('fileExists')->willReturnMap([
            ['.tmp/abc123/metadata.json', true],
            ['.tmp/abc123/chunk_0', true],
        ]);
        $filesystem->method('read')->willReturn('{}');
        $filesystem->method('readStream')->willReturnCallback(static function () {
            $stream = fopen('php://memory', 'r+');
            fwrite($stream, 'original');
            rewind($stream);

            return $stream;
        });

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('different content');
        $storage->storeChunk('abc123', 0, 'replacement', hash('sha256', 'replacement'));
    }

    #[Test]
    public function atomicChunkWriteStoresNewContent(): void
    {
        [$storage, $filesystem] = $this->createMockFilesystem();
        $filesystem->method('fileExists')->willReturnMap([
            ['.tmp/abc123/metadata.json', true],
            ['.tmp/abc123/chunk_0', false],
        ]);
        $filesystem->method('read')->willReturn('{}');
        $filesystem->expects(self::once())->method('write')->with('.tmp/abc123/chunk_0', 'data');

        self::assertSame(
            ChunkWriteResult::Stored,
            $storage->storeChunk('abc123', 0, 'data', hash('sha256', 'data')),
        );
    }

    #[Test]
    public function pendingCountIsZeroWithoutTemporaryDirectory(): void
    {
        $this->filesystem->method('directoryExists')->willReturn(false);

        self::assertSame(0, $this->storage->countPendingByContext(new UploadContext()));
    }

    #[Test]
    public function pendingCountIgnoresFilesAndCorruptSessions(): void
    {
        $file = $this->createStub(\League\Flysystem\StorageAttributes::class);
        $file->method('isDir')->willReturn(false);
        $corrupt = $this->createStub(\League\Flysystem\StorageAttributes::class);
        $corrupt->method('isDir')->willReturn(true);
        $corrupt->method('path')->willReturn('.tmp/corrupt');
        $this->filesystem->method('directoryExists')->willReturn(true);
        $this->filesystem->method('listContents')->willReturn(new DirectoryListing([$file, $corrupt]));
        $this->filesystem->method('fileExists')->willReturn(true);
        $this->filesystem->method('read')->willReturn('{invalid');

        self::assertSame(0, $this->storage->countPendingByContext(new UploadContext()));
    }

    #[Test]
    public function rejectsScalarAndListMetadata(): void
    {
        foreach (['true', '["value"]'] as $json) {
            $filesystem = $this->createStub(FilesystemOperator::class);
            $filesystem->method('fileExists')->willReturn(true);
            $filesystem->method('read')->willReturn($json);

            try {
                new FlysystemStorage($filesystem)->getMetadata('abc123');
                self::fail('Invalid metadata must be rejected.');
            } catch (StorageException $exception) {
                self::assertStringContainsString('Invalid upload metadata', $exception->getMessage());
            }
        }
    }

    #[Test]
    public function completeSessionDeletesChunksAndPersistsMetadata(): void
    {
        [$storage, $filesystem] = $this->createMockFilesystem();
        $chunk = $this->createStub(\League\Flysystem\StorageAttributes::class);
        $chunk->method('isFile')->willReturn(true);
        $chunk->method('path')->willReturn('.tmp/abc123/chunk_0');
        $other = $this->createStub(\League\Flysystem\StorageAttributes::class);
        $other->method('isFile')->willReturn(true);
        $other->method('path')->willReturn('.tmp/abc123/metadata.json');
        $filesystem->method('listContents')->willReturn(new DirectoryListing([$chunk, $other]));
        $deleted = [];
        $filesystem->expects(self::exactly(2))
            ->method('delete')
            ->willReturnCallback(static function (string $path) use (&$deleted): void {
                $deleted[] = $path;
            });
        $filesystem->expects(self::once())->method('write')->with(
            '.tmp/abc123/metadata.json',
            json_encode(['completedPath' => 'completed']),
        );

        $storage->completeSession('abc123', ['completedPath' => 'completed']);

        self::assertContains('.tmp/abc123/chunk_0', $deleted);
        // A completed session is no longer pending, so it leaves the index.
        self::assertContains('.tmp/.pending/'.new UploadContext()->fingerprint().'/abc123', $deleted);
    }

    #[Test]
    public function countsPendingUploadsForTheRequestedOwner(): void
    {
        [$storage, $filesystem] = $this->createMockFilesystem();
        $context = new UploadContext('a');
        $directory = '.tmp/.pending/'.$context->fingerprint();

        $filesystem->method('directoryExists')->with($directory)->willReturn(true);
        $marker = $this->createStub(\League\Flysystem\StorageAttributes::class);
        $marker->method('isFile')->willReturn(true);
        $filesystem->method('listContents')->with($directory, false)->willReturn(new DirectoryListing([$marker]));

        // One listing of one directory: no metadata is read, so the cost no longer
        // grows with the number of sessions the application has kept.
        self::assertSame(1, $storage->countPendingByContext($context));
    }

    #[Test]
    public function assembleWithSingleChunk(): void
    {
        [$storage, $filesystem] = $this->createMockFilesystem();

        $metadata = ['filename' => 'test.txt', 'totalChunks' => 1, 'expiresAt' => 2_000_000_000];

        $filesystem->expects($this->any())
            ->method('fileExists')
            ->willReturnMap([
                ['.tmp/123/metadata.json', true],
                ['.tmp/123/chunk_0', true],
                ['.tmp/completed/2000000000-123.txt', false],
            ]);

        $filesystem->expects($this->once())
            ->method('read')
            ->with('.tmp/123/metadata.json')
            ->willReturn(json_encode($metadata));

        $chunkStream = fopen('data://text/plain,chunk-content', 'r');
        $filesystem->expects($this->once())
            ->method('readStream')
            ->with('.tmp/123/chunk_0')
            ->willReturn($chunkStream);

        $filesystem->expects($this->once())
            ->method('writeStream')
            ->with(
                $this->stringContains('123'),
                $this->isResource()
            );

        $path = $storage->assemble('123')->path;
        $this->assertStringContainsString('123', $path);
    }

    #[Test]
    public function assemblyDeletesAnUncommittedObjectLeftByACrash(): void
    {
        [$storage, $filesystem] = $this->createMockFilesystem();
        $expiresAt = 2_000_000_000;
        $metadata = ['filename' => 'test.txt', 'fileSize' => 4, 'totalChunks' => 1];
        $filesystem->method('fileExists')->willReturnMap([
            ['.tmp/123/metadata.json', true],
            ['.tmp/completed/2000000000-123.txt', true],
            ['.tmp/123/chunk_0', true],
        ]);
        $filesystem->method('read')->with('.tmp/123/metadata.json')->willReturn(json_encode($metadata));
        $filesystem->expects(self::once())->method('delete')->with('.tmp/completed/2000000000-123.txt');
        $filesystem->method('readStream')
            ->with('.tmp/123/chunk_0')
            ->willReturnCallback(static fn () => fopen('data://text/plain,good', 'r'));
        $filesystem->expects(self::once())
            ->method('writeStream')
            ->with('.tmp/completed/2000000000-123.txt', self::isResource());

        $assembled = $storage->assemble('123', 'sha256', $expiresAt);

        self::assertSame(4, $assembled->size);
        self::assertSame(hash('sha256', 'good'), $assembled->hash);
    }

    #[Test]
    public function assembleWithMissingChunkThrows(): void
    {
        $metadata = ['filename' => 'test.txt', 'totalChunks' => 2, 'expiresAt' => 2_000_000_000];

        $this->filesystem->method('fileExists')
            ->willReturnMap([
                ['.tmp/123/metadata.json', true],
                ['.tmp/123/chunk_0', true],
                ['.tmp/123/chunk_1', false],
                ['.tmp/completed/2000000000-123.txt', false],
            ]);

        $this->filesystem->method('read')
            ->willReturn(json_encode($metadata));

        $chunkStream = fopen('data://text/plain,first-chunk', 'r');
        $this->filesystem->method('readStream')
            ->willReturn($chunkStream);

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Missing chunk: 1');

        $this->storage->assemble('123');
    }

    #[Test]
    public function assembleWithMissingSessionThrows(): void
    {
        $this->filesystem->method('fileExists')
            ->willReturn(false);

        $this->expectException(UploadSessionNotFoundException::class);

        $this->storage->assemble('nonexistent');
    }

    #[Test]
    public function verifiedAssemblyWithMissingSessionThrows(): void
    {
        $this->filesystem->method('fileExists')->willReturn(false);

        $this->expectException(UploadSessionNotFoundException::class);

        $this->storage->assemble('nonexistent');
    }

    #[Test]
    public function assemblyRejectsAnUnusableTemporaryDirectory(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'ux-upload-test-');
        self::assertIsString($path);
        try {
            $filesystem = $this->createStub(FilesystemOperator::class);
            $filesystem->method('fileExists')->willReturnMap([
                ['.tmp/abc123/metadata.json', true],
                ['.tmp/completed/2000000000-abc123.txt', false],
            ]);
            $filesystem->method('read')->willReturn(json_encode(['filename' => 'test.txt', 'totalChunks' => 1]));
            $storage = new FlysystemStorage($filesystem, $path);

            $this->expectException(StorageException::class);
            $this->expectExceptionMessage('Cannot create Flysystem assembly directory');
            $storage->assemble('abc123', expiresAt: 2_000_000_000);
        } finally {
            @unlink($path);
        }
    }

    #[Test]
    public function assemblyPreservesFailureWhenCleanupAlsoFails(): void
    {
        $filesystem = $this->createStub(FilesystemOperator::class);
        $filesystem->method('fileExists')->willReturnMap([
            ['.tmp/abc123/metadata.json', true],
            ['.tmp/completed/2000000000-abc123.txt', false],
            ['.tmp/abc123/chunk_0', false],
        ]);
        $filesystem->method('read')->willReturn(json_encode(['filename' => 'test.txt', 'totalChunks' => 1]));
        $filesystem->method('delete')->willThrowException(new \RuntimeException('cleanup failed'));
        $storage = new FlysystemStorage($filesystem);

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Missing chunk: 0');

        $storage->assemble('abc123', expiresAt: 2_000_000_000);
    }

    #[Test]
    public function assemblyReportsTemporaryFileCreationFailure(): void
    {
        $this->prepareAssembly();
        NativeFunctions::mock('Symfony\\UX\\Upload\\Storage\\tempnam', false);

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Cannot create temporary file');

        $this->storage->assemble('native-failure', expiresAt: 2_000_000_000);
    }

    #[Test]
    public function assemblyReportsTemporaryFileOpenFailure(): void
    {
        $this->prepareAssembly();
        NativeFunctions::mock('Symfony\\UX\\Upload\\Storage\\fopen', false);

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Cannot open temporary file');

        $this->storage->assemble('native-failure', expiresAt: 2_000_000_000);
    }

    #[Test]
    public function assemblyReportsChunkReadFailure(): void
    {
        $this->prepareAssembly();
        NativeFunctions::mock('Symfony\\UX\\Upload\\Storage\\fread', false);

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Cannot read chunk: 0');

        $this->storage->assemble('native-failure', expiresAt: 2_000_000_000);
    }

    #[Test]
    public function assemblyIgnoresAnEmptyIntermediateRead(): void
    {
        $this->prepareAssembly();
        NativeFunctions::mock(
            'Symfony\\UX\\Upload\\Storage\\fread',
            '',
            NativeFunctions::PASSTHROUGH,
        );

        self::assertSame(
            4,
            $this->storage->assemble('native-failure', expiresAt: 2_000_000_000)->size,
        );
    }

    #[Test]
    public function assemblyReportsNativeWriteFailure(): void
    {
        $this->prepareAssembly();
        NativeFunctions::mock('Symfony\\UX\\Upload\\Storage\\fwrite', 0);

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Cannot write Flysystem assembly file');

        $this->storage->assemble('native-failure', expiresAt: 2_000_000_000);
    }

    #[Test]
    public function assembleGeneratesPathWithExtension(): void
    {
        [$storage, $filesystem] = $this->createMockFilesystem();

        $metadata = ['filename' => 'report.pdf', 'totalChunks' => 1, 'expiresAt' => 2_000_000_000];

        $filesystem->method('fileExists')
            ->willReturnMap([
                ['.tmp/456/metadata.json', true],
                ['.tmp/456/chunk_0', true],
                ['.tmp/completed/2000000000-456.pdf', false],
            ]);

        $filesystem->method('read')
            ->with('.tmp/456/metadata.json')
            ->willReturn(json_encode($metadata));

        $chunkStream = fopen('data://text/plain,content', 'r');
        $filesystem->method('readStream')
            ->with('.tmp/456/chunk_0')
            ->willReturn($chunkStream);

        $filesystem->expects($this->once())
            ->method('writeStream')
            ->with(
                '.tmp/completed/2000000000-456.pdf',
                $this->isResource()
            );

        $path = $storage->assemble('456')->path;
        $this->assertStringEndsWith('.pdf', $path);
    }

    #[Test]
    public function rejectsDotsInUploadId(): void
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Invalid upload ID');

        $this->storage->initiate('upload.123', ['filename' => 'test.txt', 'totalChunks' => 1]);
    }

    #[Test]
    public function pruneSkipsWhenNoTmpDirectory(): void
    {
        [$storage, $filesystem] = $this->createMockFilesystem();

        $filesystem->method('directoryExists')
            ->with('.tmp')
            ->willReturn(false);

        $filesystem->expects(self::never())
            ->method('listContents');

        $storage->prune(3600);
    }

    #[Test]
    public function pruneDeletesStaleUploads(): void
    {
        [$storage, $filesystem] = $this->createMockFilesystem();

        $filesystem->method('directoryExists')
            ->willReturnCallback(static fn (string $path): bool => '.tmp' === $path);

        $staleMetadata = json_encode(['createdAt' => time() - 7200]);

        $item = $this->createStub(\League\Flysystem\StorageAttributes::class);
        $item->method('isDir')->willReturn(true);
        $item->method('path')->willReturn('.tmp/stale-upload');

        $listing = new DirectoryListing([$item]);
        $filesystem->method('listContents')
            ->with('.tmp')
            ->willReturn($listing);

        $filesystem->method('fileExists')
            ->with('.tmp/stale-upload/metadata.json')
            ->willReturn(true);

        $filesystem->method('read')
            ->with('.tmp/stale-upload/metadata.json')
            ->willReturn($staleMetadata);

        $filesystem->expects(self::once())
            ->method('deleteDirectory')
            ->with('.tmp/stale-upload');

        $storage->prune(3600);
    }

    #[Test]
    public function pruneKeepsFreshUploads(): void
    {
        [$storage, $filesystem] = $this->createMockFilesystem();

        $filesystem->method('directoryExists')
            ->willReturnCallback(static fn (string $path): bool => '.tmp' === $path);

        $freshMetadata = json_encode(['createdAt' => time() - 60]);

        $item = $this->createStub(\League\Flysystem\StorageAttributes::class);
        $item->method('isDir')->willReturn(true);
        $item->method('path')->willReturn('.tmp/fresh-upload');

        $listing = new DirectoryListing([$item]);
        $filesystem->method('listContents')
            ->with('.tmp')
            ->willReturn($listing);

        $filesystem->method('fileExists')
            ->with('.tmp/fresh-upload/metadata.json')
            ->willReturn(true);

        $filesystem->method('read')
            ->with('.tmp/fresh-upload/metadata.json')
            ->willReturn($freshMetadata);

        $filesystem->expects(self::never())
            ->method('deleteDirectory');

        $storage->prune(3600);
    }

    #[Test]
    public function pruneDeletesExpiredCompletedSessionAndObject(): void
    {
        [$storage, $filesystem] = $this->createMockFilesystem();
        $completedPath = '.tmp/completed/'.(time() + 3600).'-0123456789abcdef0123456789abcdef.txt';
        $item = $this->createStub(\League\Flysystem\StorageAttributes::class);
        $item->method('isDir')->willReturn(true);
        $item->method('path')->willReturn('.tmp/completed-upload');
        $filesystem->method('directoryExists')->willReturnCallback(static fn (string $path): bool => '.tmp' === $path);
        $filesystem->method('listContents')->with('.tmp')->willReturn(new DirectoryListing([$item]));
        $filesystem->method('fileExists')->willReturnMap([
            ['.tmp/completed-upload/metadata.json', true],
            [$completedPath, true],
        ]);
        $filesystem->method('read')->willReturn(json_encode([
            'expiresAt' => time() - 1,
            'completedPath' => $completedPath,
        ]));
        $filesystem->expects(self::once())->method('delete')->with($completedPath);
        $filesystem->expects(self::once())->method('deleteDirectory')->with('.tmp/completed-upload');

        $storage->prune(3600);
    }

    #[Test]
    public function pruneSkipsSessionWhenItsLifecycleLockIsBusy(): void
    {
        $item = $this->createStub(\League\Flysystem\StorageAttributes::class);
        $item->method('isDir')->willReturn(true);
        $item->method('path')->willReturn('.tmp/busy');
        $this->filesystem->method('directoryExists')->willReturnCallback(
            static fn (string $path): bool => '.tmp' === $path,
        );
        $this->filesystem->method('listContents')->willReturn(new DirectoryListing([$item]));
        $lock = $this->createStub(SharedLockInterface::class);
        $lock->method('acquire')->willReturn(false);
        $lockFactory = $this->createStub(LockFactory::class);
        $lockFactory->method('createLock')->willReturn($lock);
        $storage = new FlysystemStorage($this->filesystem, lockFactory: $lockFactory);

        $storage->prune(3600);

        self::addToAssertionCount(1);
    }

    #[Test]
    public function pruneDeletesUploadWithMissingMetadata(): void
    {
        [$storage, $filesystem] = $this->createMockFilesystem();

        $filesystem->method('directoryExists')
            ->willReturnCallback(static fn (string $path): bool => '.tmp' === $path);

        $item = $this->createStub(\League\Flysystem\StorageAttributes::class);
        $item->method('isDir')->willReturn(true);
        $item->method('path')->willReturn('.tmp/orphan-upload');

        $listing = new DirectoryListing([$item]);
        $filesystem->method('listContents')
            ->with('.tmp')
            ->willReturn($listing);

        $filesystem->method('fileExists')
            ->with('.tmp/orphan-upload/metadata.json')
            ->willReturn(false);

        $filesystem->expects(self::once())
            ->method('deleteDirectory')
            ->with('.tmp/orphan-upload');

        $storage->prune(3600);
    }

    #[Test]
    public function pruneDeletesUploadWithInvalidMetadataJson(): void
    {
        [$storage, $filesystem] = $this->createMockFilesystem();

        $filesystem->method('directoryExists')
            ->willReturnCallback(static fn (string $path): bool => '.tmp' === $path);

        $item = $this->createStub(\League\Flysystem\StorageAttributes::class);
        $item->method('isDir')->willReturn(true);
        $item->method('path')->willReturn('.tmp/corrupt-upload');

        $listing = new DirectoryListing([$item]);
        $filesystem->method('listContents')
            ->with('.tmp')
            ->willReturn($listing);

        $filesystem->method('fileExists')
            ->with('.tmp/corrupt-upload/metadata.json')
            ->willReturn(true);

        $filesystem->method('read')
            ->with('.tmp/corrupt-upload/metadata.json')
            ->willReturn('{invalid json');

        $filesystem->expects(self::once())
            ->method('deleteDirectory')
            ->with('.tmp/corrupt-upload');

        $storage->prune(3600);
    }

    #[Test]
    public function pruneSkipsNonDirectoryItems(): void
    {
        [$storage, $filesystem] = $this->createMockFilesystem();

        $filesystem->method('directoryExists')
            ->willReturnCallback(static fn (string $path): bool => '.tmp' === $path);

        $fileItem = $this->createStub(\League\Flysystem\StorageAttributes::class);
        $fileItem->method('isDir')->willReturn(false);
        $fileItem->method('path')->willReturn('.tmp/some-file');

        $listing = new DirectoryListing([$fileItem]);
        $filesystem->method('listContents')
            ->with('.tmp')
            ->willReturn($listing);

        $filesystem->expects(self::never())
            ->method('deleteDirectory');

        $storage->prune(3600);
    }

    #[Test]
    public function pruneDeletesOnlyExpiredGeneratedCompletedFiles(): void
    {
        [$storage, $filesystem] = $this->createMockFilesystem();
        $expiredPath = '.tmp/completed/'.(time() - 1).'-0123456789abcdef0123456789abcdef.txt';
        $freshPath = '.tmp/completed/'.(time() + 3600).'-fedcba9876543210fedcba9876543210.txt';
        $expired = $this->createStub(\League\Flysystem\StorageAttributes::class);
        $expired->method('isFile')->willReturn(true);
        $expired->method('path')->willReturn($expiredPath);
        $fresh = $this->createStub(\League\Flysystem\StorageAttributes::class);
        $fresh->method('isFile')->willReturn(true);
        $fresh->method('path')->willReturn($freshPath);
        $completedDirectory = $this->createStub(\League\Flysystem\StorageAttributes::class);
        $completedDirectory->method('isDir')->willReturn(true);
        $completedDirectory->method('path')->willReturn('.tmp/completed');

        $filesystem->method('directoryExists')->willReturn(true);
        $filesystem->method('listContents')->willReturnMap([
            ['.tmp/completed', false, new DirectoryListing([$expired, $fresh])],
            ['.tmp', false, new DirectoryListing([$completedDirectory])],
            ['.tmp/.pending', true, new DirectoryListing([])],
        ]);
        $filesystem->expects(self::once())->method('delete')->with($expiredPath);

        $storage->prune(3600);
    }

    #[Test]
    public function rejectsEmptyUploadId(): void
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Invalid upload ID');

        $this->storage->initiate('', ['filename' => 'test.txt']);
    }

    #[Test]
    public function rejectsDoubleDotUploadId(): void
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Invalid upload ID');

        $this->storage->listChunks('..');
    }

    private function prepareAssembly(): void
    {
        $this->filesystem->method('fileExists')->willReturnMap([
            ['.tmp/native-failure/metadata.json', true],
            ['.tmp/completed/2000000000-native-failure.txt', false],
            ['.tmp/native-failure/chunk_0', true],
        ]);
        $this->filesystem->method('read')->willReturn(json_encode([
            'filename' => 'test.txt',
            'fileSize' => 4,
            'totalChunks' => 1,
        ]));
        $this->filesystem->method('readStream')->willReturnCallback(
            static fn () => fopen('data://text/plain,data', 'r'),
        );
    }
}
