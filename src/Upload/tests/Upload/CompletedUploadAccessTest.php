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
use Symfony\UX\Upload\Storage\StorageInterface;
use Symfony\UX\Upload\Tests\NativeFunctions;
use Symfony\UX\Upload\Upload\CompletedUploadAccess;

#[CoversClass(CompletedUploadAccess::class)]
final class CompletedUploadAccessTest extends TestCase
{
    protected function tearDown(): void
    {
        NativeFunctions::reset();
    }

    public function testOpenStreamReturnsStorageStream()
    {
        $stream = fopen('php://temp', 'w+');
        self::assertIsResource($stream);
        $storage = $this->createStub(StorageInterface::class);
        $storage->method('read')->willReturn($stream);

        self::assertSame($stream, new CompletedUploadAccess($storage)->openStream('path'));

        fclose($stream);
    }

    public function testOpenStreamRejectsUnsupportedStorageContent()
    {
        $storage = $this->createStub(StorageInterface::class);
        $storage->method('read')->willReturn(123);

        $this->expectException(UploadException::class);
        $this->expectExceptionMessage('unsupported content');

        new CompletedUploadAccess($storage)->openStream('path');
    }

    public function testOpenStreamReportsTemporaryStreamCreationFailure()
    {
        $storage = $this->createStub(StorageInterface::class);
        $storage->method('read')->willReturn('data');
        NativeFunctions::mock('Symfony\\UX\\Upload\\Upload\\fopen', false);

        $this->expectException(UploadException::class);
        $this->expectExceptionMessage('Unable to create');

        new CompletedUploadAccess($storage)->openStream('path');
    }

    public function testOpenStreamClosesStreamAfterNativeWriteFailure()
    {
        $storage = $this->createStub(StorageInterface::class);
        $storage->method('read')->willReturn('data');
        NativeFunctions::mock('Symfony\\UX\\Upload\\Upload\\fwrite', 0);

        $this->expectException(UploadException::class);
        $this->expectExceptionMessage('Unable to write');

        new CompletedUploadAccess($storage)->openStream('path');
    }
}
