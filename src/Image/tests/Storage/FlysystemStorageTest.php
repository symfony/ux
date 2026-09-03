<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Storage;

use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\Image\ImageAsset;
use Symfony\UX\Image\Storage\FlysystemStorage;
use Symfony\UX\Image\Storage\StoragePath;

/**
 * @requires interface League\Flysystem\FilesystemOperator
 */
#[CoversClass(FlysystemStorage::class)]
final class FlysystemStorageTest extends TestCase
{
    public function testStore()
    {
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->expects(self::once())->method('writeStream');

        $storage = new FlysystemStorage($filesystem, '/cdn');

        $tmpFile = sys_get_temp_dir().'/fly_test_'.uniqid().'.jpg';
        file_put_contents($tmpFile, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true));

        $file = new UploadedFile($tmpFile, 'payload.php', 'application/x-php', null, true);

        $path = $storage->store($file, 'media');

        self::assertStringStartsWith('/', $path);
        self::assertStringEndsWith('.png', $path);
        self::assertStringNotContainsString('.php', $path);

        @unlink($tmpFile);
    }

    public function testStoreClosesStreamWhenFlysystemFails()
    {
        $writtenStream = null;
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->expects(self::once())
            ->method('writeStream')
            ->willReturnCallback(static function (string $path, $stream) use (&$writtenStream): never {
                $writtenStream = $stream;

                throw new \RuntimeException('Storage unavailable.');
            });

        $tmpFile = sys_get_temp_dir().'/fly_test_'.uniqid().'.png';
        file_put_contents($tmpFile, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true));

        try {
            new FlysystemStorage($filesystem)->store(new UploadedFile($tmpFile, 'image.png', 'image/png', null, true), 'media');
            self::fail('The Flysystem exception should be propagated.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Storage unavailable.', $exception->getMessage());
            self::assertFalse(\is_resource($writtenStream));
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testDeleteExistingFile()
    {
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->expects(self::once())->method('fileExists')->willReturn(true);
        $filesystem->expects(self::once())->method('delete');

        $storage = new FlysystemStorage($filesystem);
        $asset = new ImageAsset('media', '/images/photo.jpg');

        self::assertTrue($storage->delete($asset));
    }

    public function testDeleteNonExistingFile()
    {
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->expects(self::once())->method('fileExists')->willReturn(false);
        $filesystem->expects(self::never())->method('delete');

        $storage = new FlysystemStorage($filesystem);
        $asset = new ImageAsset('media', '/images/photo.jpg');

        self::assertFalse($storage->delete($asset));
    }

    public function testExists()
    {
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->expects(self::exactly(2))->method('fileExists')
            ->willReturnOnConsecutiveCalls(true, false);

        $storage = new FlysystemStorage($filesystem);

        $asset = new ImageAsset('media', '/images/photo.jpg');

        self::assertTrue($storage->exists($asset));
        self::assertFalse($storage->exists($asset));
    }

    public function testGetPublicUrlWithPrefix()
    {
        $filesystem = $this->createStub(FilesystemOperator::class);
        $storage = new FlysystemStorage($filesystem, 'https://cdn.example.com');

        $asset = new ImageAsset('media', '/images/photo.jpg');

        self::assertSame('https://cdn.example.com/images/photo.jpg', $storage->getPublicUrl($asset));
    }

    public function testGetPublicUrlWithoutPrefix()
    {
        $filesystem = $this->createStub(FilesystemOperator::class);
        $storage = new FlysystemStorage($filesystem);

        $asset = new ImageAsset('media', '/images/photo.jpg');

        self::assertSame('/images/photo.jpg', $storage->getPublicUrl($asset));
    }

    public function testGetPublicUrlWithVariant()
    {
        $filesystem = $this->createStub(FilesystemOperator::class);
        $storage = new FlysystemStorage($filesystem, '/uploads');

        $asset = new ImageAsset('media', '/photo.jpg');

        self::assertSame('/uploads/thumb.jpg', $storage->getPublicUrl($asset, '/thumb.jpg'));
    }

    public function testGetFilePath()
    {
        $filesystem = $this->createStub(FilesystemOperator::class);
        $storage = new FlysystemStorage($filesystem);

        $asset = new ImageAsset('media', '/images/photo.jpg');

        self::assertSame('images/photo.jpg', $storage->getFilePath($asset));
    }

    public function testGetFilePathWithoutStoragePart()
    {
        $filesystem = $this->createStub(FilesystemOperator::class);
        $storage = new FlysystemStorage($filesystem);

        $asset = new ImageAsset('media', '/photo.jpg');

        self::assertSame('photo.jpg', $storage->getFilePath($asset));
    }

    public function testStreamOperations()
    {
        $read = fopen('php://temp', 'w+');
        $write = fopen('php://temp', 'w+');
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->expects(self::once())->method('readStream')->with('source.jpg')->willReturn($read);
        $filesystem->expects(self::exactly(2))->method('fileExists')->willReturnOnConsecutiveCalls(false, true);
        $filesystem->expects(self::once())->method('writeStream')->with('target.jpg', $write);
        $filesystem->expects(self::once())->method('delete')->with('target.jpg');
        $storage = new FlysystemStorage($filesystem);

        self::assertSame($read, $storage->readStream('media', new StoragePath('source.jpg')));
        $storage->writeStream('media', new StoragePath('target.jpg'), $write);
        $storage->deletePath('media', new StoragePath('target.jpg'));

        fclose($read);
        fclose($write);
    }

    public function testReadStreamRejectsInvalidResult()
    {
        $filesystem = $this->createStub(FilesystemOperator::class);
        $filesystem->method('readStream')->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('did not return a stream');

        new FlysystemStorage($filesystem)->readStream('media', new StoragePath('source.jpg'));
    }

    public function testWriteStreamRejectsInvalidResource()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('requires a stream resource');

        new FlysystemStorage($this->createStub(FilesystemOperator::class))->writeStream('media', new StoragePath('target.jpg'), false);
    }

    public function testWriteStreamRejectsExistingImmutableObject()
    {
        $stream = fopen('php://temp', 'w+');
        $filesystem = $this->createStub(FilesystemOperator::class);
        $filesystem->method('fileExists')->willReturn(true);

        try {
            new FlysystemStorage($filesystem)->writeStream('media', new StoragePath('target.jpg'), $stream);
            self::fail('An existing immutable object must be rejected.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('already exists', $exception->getMessage());
        } finally {
            fclose($stream);
        }
    }

    public function testDeletePathIgnoresMissingObject()
    {
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->expects(self::once())->method('fileExists')->willReturn(false);
        $filesystem->expects(self::never())->method('delete');

        new FlysystemStorage($filesystem)->deletePath('media', new StoragePath('missing.jpg'));
    }
}
