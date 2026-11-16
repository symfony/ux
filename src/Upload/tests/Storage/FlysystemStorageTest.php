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
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;
use Symfony\UX\Upload\Exception\InvalidArgumentException;
use Symfony\UX\Upload\Exception\StorageException;
use Symfony\UX\Upload\Storage\ChunkWriteResult;
use Symfony\UX\Upload\Storage\FlysystemStorage;
use Symfony\UX\Upload\Tests\NativeFunctions;

#[CoversClass(FlysystemStorage::class)]
final class FlysystemStorageTest extends TestCase
{
    private FlysystemStorage $storage;
    private FilesystemOperator&Stub $filesystem;

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

    public function testAtomicChunkRetryAcceptsTheSameContent(): void
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
                self::assertIsResource($stream);
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

    public function testAtomicChunkRetryRejectsDifferentContent(): void
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
            self::assertIsResource($stream);
            fwrite($stream, 'original');
            rewind($stream);

            return $stream;
        });

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('different content');
        $storage->storeChunk('abc123', 0, 'replacement', hash('sha256', 'replacement'));
    }

    public function testAtomicChunkWriteStoresNewContent(): void
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

    public function testAssemblyRejectsAnUnusableTemporaryDirectory(): void
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

    public function testAssemblyPreservesFailureWhenCleanupAlsoFails(): void
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

    public function testAssemblyReportsTemporaryFileCreationFailure(): void
    {
        $this->prepareAssembly();
        NativeFunctions::mock('Symfony\\UX\\Upload\\Storage\\tempnam', false);

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Cannot create temporary file');

        $this->storage->assemble('native-failure', expiresAt: 2_000_000_000);
    }

    public function testAssemblyReportsTemporaryFileOpenFailure(): void
    {
        $this->prepareAssembly();
        NativeFunctions::mock('Symfony\\UX\\Upload\\Storage\\fopen', false);

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Cannot open temporary file');

        $this->storage->assemble('native-failure', expiresAt: 2_000_000_000);
    }

    public function testAssemblyReportsChunkReadFailure(): void
    {
        $this->prepareAssembly();
        NativeFunctions::mock('Symfony\\UX\\Upload\\Storage\\fread', false);

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Cannot read chunk: 0');

        $this->storage->assemble('native-failure', expiresAt: 2_000_000_000);
    }

    public function testAssemblyIgnoresAnEmptyIntermediateRead(): void
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

    public function testAssemblyReportsNativeWriteFailure(): void
    {
        $this->prepareAssembly();
        NativeFunctions::mock('Symfony\\UX\\Upload\\Storage\\fwrite', 0);

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Cannot write Flysystem assembly file');

        $this->storage->assemble('native-failure', expiresAt: 2_000_000_000);
    }

    public function testPruneSkipsSessionWhenItsLifecycleLockIsBusy(): void
    {
        $item = $this->createStub(\League\Flysystem\StorageAttributes::class);
        $item->method('isDir')->willReturn(true);
        $item->method('path')->willReturn('.tmp/busy');
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->method('directoryExists')->willReturnCallback(
            static fn (string $path): bool => '.tmp' === $path,
        );
        $filesystem->method('listContents')->willReturn(new DirectoryListing([$item]));
        $filesystem->method('fileExists')->willReturn(true);
        $filesystem->method('read')->willReturn(json_encode(['createdAt' => time() - 7200]));
        $filesystem->expects(self::never())->method('deleteDirectory');
        $filesystem->expects(self::never())->method('delete');
        $lock = $this->createMock(SharedLockInterface::class);
        $lock->expects(self::once())->method('acquire')->willReturn(false);
        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory->expects(self::once())
            ->method('createLock')
            ->with('ux_upload_lifecycle_busy')
            ->willReturn($lock);
        $storage = new FlysystemStorage($filesystem, lockFactory: $lockFactory);

        $storage->prune(3600);
    }

    /**
     * @return iterable<string, array{\Closure(FilesystemOperator&Stub): void, \Closure(FlysystemStorage): void}>
     */
    public static function failingOperations(): iterable
    {
        yield 'read' => [
            static function (Stub $filesystem): void {
                $filesystem->method('readStream')->willThrowException(UnableToReadFile::fromLocation('file.txt'));
            },
            static function (FlysystemStorage $storage): void {
                $storage->read('file.txt');
            },
        ];
        yield 'write a string' => [
            static function (Stub $filesystem): void {
                $filesystem->method('write')->willThrowException(UnableToWriteFile::atLocation('file.txt'));
            },
            static function (FlysystemStorage $storage): void {
                $storage->write('file.txt', 'data');
            },
        ];
        yield 'write a stream' => [
            static function (Stub $filesystem): void {
                $filesystem->method('writeStream')->willThrowException(UnableToWriteFile::atLocation('file.txt'));
            },
            static function (FlysystemStorage $storage): void {
                $stream = fopen('php://memory', 'r');
                self::assertIsResource($stream);
                $storage->write('file.txt', $stream);
            },
        ];
        yield 'delete' => [
            static function (Stub $filesystem): void {
                $filesystem->method('fileExists')->willReturn(true);
                $filesystem->method('delete')->willThrowException(UnableToDeleteFile::atLocation('file.txt'));
            },
            static function (FlysystemStorage $storage): void {
                $storage->delete('file.txt');
            },
        ];
        yield 'check existence before delete' => [
            static function (Stub $filesystem): void {
                $filesystem->method('fileExists')->willThrowException(UnableToCheckFileExistence::forLocation('file.txt'));
            },
            static function (FlysystemStorage $storage): void {
                $storage->delete('file.txt');
            },
        ];
        yield 'initiate' => [
            static function (Stub $filesystem): void {
                $filesystem->method('write')->willThrowException(UnableToWriteFile::atLocation('.tmp/abc123/metadata.json'));
            },
            static function (FlysystemStorage $storage): void {
                $storage->initiate('abc123', ['filename' => 'file.txt']);
            },
        ];
        yield 'read session metadata' => [
            static function (Stub $filesystem): void {
                $filesystem->method('fileExists')->willReturn(true);
                $filesystem->method('read')->willThrowException(UnableToReadFile::fromLocation('.tmp/abc123/metadata.json'));
            },
            static function (FlysystemStorage $storage): void {
                $storage->storeChunk('abc123', 0, 'data', hash('sha256', 'data'));
            },
        ];
        yield 'store a chunk' => [
            static function (Stub $filesystem): void {
                $filesystem->method('fileExists')->willReturnMap([
                    ['.tmp/abc123/metadata.json', true],
                    ['.tmp/abc123/chunk_0', false],
                ]);
                $filesystem->method('read')->willReturn('{}');
                $filesystem->method('write')->willThrowException(UnableToWriteFile::atLocation('.tmp/abc123/chunk_0'));
            },
            static function (FlysystemStorage $storage): void {
                $storage->storeChunk('abc123', 0, 'data', hash('sha256', 'data'));
            },
        ];
        yield 'publish an assembled file' => [
            static function (Stub $filesystem): void {
                $filesystem->method('fileExists')->willReturnMap([
                    ['.tmp/abc123/metadata.json', true],
                    ['.tmp/completed/2000000000-abc123.txt', false],
                    ['.tmp/abc123/chunk_0', true],
                ]);
                $filesystem->method('read')->willReturn(json_encode(['filename' => 'file.txt', 'totalChunks' => 1]));
                $filesystem->method('readStream')->willReturnCallback(static fn () => fopen('data://text/plain,data', 'r'));
                $filesystem->method('writeStream')->willThrowException(UnableToWriteFile::atLocation('.tmp/completed/2000000000-abc123.txt'));
            },
            static function (FlysystemStorage $storage): void {
                $storage->assemble('abc123', expiresAt: 2_000_000_000);
            },
        ];
    }

    /**
     * @param \Closure(FilesystemOperator&Stub): void $configure
     * @param \Closure(FlysystemStorage): void        $operation
     */
    #[DataProvider('failingOperations')]
    public function testFilesystemFailuresSurfaceAsStorageExceptions(\Closure $configure, \Closure $operation): void
    {
        $filesystem = $this->createStub(FilesystemOperator::class);
        $configure($filesystem);

        try {
            $operation(new FlysystemStorage($filesystem));
            self::fail('The filesystem failure must be reported.');
        } catch (StorageException $exception) {
            self::assertInstanceOf(FilesystemException::class, $exception->getPrevious());
        }
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
