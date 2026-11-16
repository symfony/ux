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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\UX\Upload\Exception\StorageException;
use Symfony\UX\Upload\Storage\LocalStorage;
use Symfony\UX\Upload\Tests\NativeFunctions;

#[CoversClass(LocalStorage::class)]
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

    public function testReadThrowsWhenFileCannotBeOpened(): void
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
            self::assertStringContainsString('Cannot open file for reading', $e->getMessage());
        } finally {
            chmod($fullPath, 0o644);
        }
    }

    public function testGetDirectoryReturnsConfiguredPath(): void
    {
        self::assertSame($this->tempDir, $this->storage->getDirectory());
    }

    public function testPruneSkipsAnUploadWhoseLifecycleLockIsHeld(): void
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

    public function testAssemblyWrapsPublicationFailureAndRemovesPartialFile(): void
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

    public function testAssembleThrowsWhenFileCannotBeOpenedForWriting(): void
    {
        $uploadId = 'fail-id';
        $this->storage->initiate($uploadId, ['filename' => 'f', 'totalChunks' => 1]);
        $this->storage->storeChunk($uploadId, 0, 'data', hash('sha256', 'data'));
        NativeFunctions::mock('Symfony\\UX\\Upload\\Storage\\fopen', false);

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Cannot open file for writing');

        $this->storage->assemble($uploadId);
    }

    public function testAtomicChunkWriteReportsNativeWriteFailure(): void
    {
        $this->storage->initiate('write-failure', ['filename' => 'f', 'totalChunks' => 1]);
        NativeFunctions::mock('Symfony\\UX\\Upload\\Storage\\fwrite', false);

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Unable to write chunk 0');

        $this->storage->storeChunk('write-failure', 0, 'data', hash('sha256', 'data'));
    }

    public function testAssemblyReportsTemporaryFileCreationFailure(): void
    {
        $this->prepareAssembly('tempnam-failure');
        NativeFunctions::mock('Symfony\\UX\\Upload\\Storage\\tempnam', false);

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Cannot create temporary assembly file');

        $this->storage->assemble('tempnam-failure');
    }

    public function testAssemblyReportsChunkOpenFailure(): void
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

    public function testAssemblyReportsChunkReadFailure(): void
    {
        $this->prepareAssembly('chunk-read-failure');
        NativeFunctions::mock('Symfony\\UX\\Upload\\Storage\\fread', false);

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Cannot read chunk: 0');

        $this->storage->assemble('chunk-read-failure');
    }

    public function testAssemblyIgnoresAnEmptyIntermediateRead(): void
    {
        $this->prepareAssembly('empty-read');
        NativeFunctions::mock(
            'Symfony\\UX\\Upload\\Storage\\fread',
            '',
            NativeFunctions::PASSTHROUGH,
        );

        $assembled = $this->storage->assemble('empty-read');

        self::assertSame(4, $assembled->size);
        $stream = $this->storage->read($assembled->path);
        self::assertSame('data', stream_get_contents($stream));
        fclose($stream);
    }

    public function testAssemblyReportsNativeWriteFailure(): void
    {
        $this->prepareAssembly('assembly-write-failure');
        NativeFunctions::mock('Symfony\\UX\\Upload\\Storage\\fwrite', 0);

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Cannot write assembled upload');

        $this->storage->assemble('assembly-write-failure');
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
