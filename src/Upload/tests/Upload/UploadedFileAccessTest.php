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
use Symfony\UX\Upload\Exception\UploadException;
use Symfony\UX\Upload\Storage\InMemoryStorage;
use Symfony\UX\Upload\Storage\StorageInterface;
use Symfony\UX\Upload\Tests\NativeFunctions;
use Symfony\UX\Upload\Upload\UploadedFileAccess;

#[CoversClass(UploadedFileAccess::class)]
final class UploadedFileAccessTest extends TestCase
{
    protected function tearDown(): void
    {
        NativeFunctions::reset();
    }

    public function testOpenStreamWrapsStringContentInAReadableStream(): void
    {
        $storage = new InMemoryStorage();
        $storage->write('.tmp/completed/file.bin', "binary\0content");

        $stream = new UploadedFileAccess($storage)->openStream('.tmp/completed/file.bin');

        self::assertIsResource($stream);
        self::assertSame("binary\0content", stream_get_contents($stream));
        fclose($stream);
    }

    public function testDeleteRemovesTheObjectAndAbortsTheSession(): void
    {
        $storage = new InMemoryStorage();
        $storage->initiate('upload-1', ['filename' => 'file.txt']);
        $storage->write('.tmp/completed/file.txt', 'data');
        $storage->write('.tmp/completed/other.txt', 'data');

        new UploadedFileAccess($storage)->delete('upload-1', '.tmp/completed/file.txt');

        self::assertFalse($storage->exists('.tmp/completed/file.txt'));
        self::assertNull($storage->getMetadata('upload-1'));
        self::assertTrue($storage->exists('.tmp/completed/other.txt'));
    }

    public function testOpenStreamReturnsStorageStream(): void
    {
        $stream = fopen('php://temp', 'w+');
        self::assertIsResource($stream);
        $storage = $this->createStub(StorageInterface::class);
        $storage->method('read')->willReturn($stream);

        self::assertSame($stream, new UploadedFileAccess($storage)->openStream('path'));

        fclose($stream);
    }

    public function testOpenStreamRejectsUnsupportedStorageContent(): void
    {
        $storage = $this->createStub(StorageInterface::class);
        $storage->method('read')->willReturn(123);

        $this->expectException(UploadException::class);
        $this->expectExceptionMessage('unsupported content');

        new UploadedFileAccess($storage)->openStream('path');
    }

    public function testOpenStreamReportsTemporaryStreamCreationFailure(): void
    {
        $storage = $this->createStub(StorageInterface::class);
        $storage->method('read')->willReturn('data');
        NativeFunctions::mock('Symfony\\UX\\Upload\\Upload\\fopen', false);

        $this->expectException(UploadException::class);
        $this->expectExceptionMessage('Unable to create');

        new UploadedFileAccess($storage)->openStream('path');
    }

    public function testOpenStreamClosesStreamAfterNativeWriteFailure(): void
    {
        $storage = $this->createStub(StorageInterface::class);
        $storage->method('read')->willReturn('data');
        NativeFunctions::mock('Symfony\\UX\\Upload\\Upload\\fwrite', 0);

        $this->expectException(UploadException::class);
        $this->expectExceptionMessage('Unable to write');

        new UploadedFileAccess($storage)->openStream('path');
    }
}
