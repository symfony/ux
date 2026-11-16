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
use Symfony\Component\HttpFoundation\File\UploadedFile as HttpUploadedFile;
use Symfony\UX\Upload\Exception\UploadException;
use Symfony\UX\Upload\Storage\StorageInterface;
use Symfony\UX\Upload\Upload\UploadedFile;
use Symfony\UX\Upload\Upload\UploadedFileAccess;

#[CoversClass(UploadedFile::class)]
final class UploadedFileTest extends TestCase
{
    public function testMetadataAccessDoesNotReadStorage(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(self::never())->method('read');
        $upload = $this->upload($storage);

        self::assertSame('document.txt', $upload->getClientOriginalName());
        self::assertSame('text/plain', $upload->getMimeType());
        self::assertSame(4, $upload->getSize());
        self::assertSame('.tmp/completed/2000000000-0123456789abcdef0123456789abcdef.txt', $upload->getStoragePath());
        self::assertSame('0123456789abcdef0123456789abcdef', $upload->getId());
        self::assertSame('default', $upload->getUploaderName());
        self::assertSame('2026-01-01T00:00:00+00:00', $upload->getCreatedAt()->format(\DATE_ATOM));
        self::assertGreaterThan($upload->getCreatedAt(), $upload->getExpiresAt());
        self::assertNull($upload->getChecksum());
        self::assertNull($upload->getChecksumAlgorithm());
        self::assertNull($upload->getOwnerId());
        self::assertNull($upload->getTenantId());
        self::assertNull($upload->getFieldName());
    }

    public function testOpenStreamPerformsTheExplicitRead(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(self::once())
            ->method('read')
            ->with('.tmp/completed/2000000000-0123456789abcdef0123456789abcdef.txt')
            ->willReturn('data');

        $stream = $this->upload($storage)->openStream();

        self::assertSame('data', stream_get_contents($stream));
        fclose($stream);
    }

    public function testDeleteRemovesOnlyTheTemporaryObject(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(self::once())
            ->method('delete')
            ->with('.tmp/completed/2000000000-0123456789abcdef0123456789abcdef.txt');
        $storage->expects(self::once())
            ->method('abort')
            ->with('0123456789abcdef0123456789abcdef');

        $this->upload($storage)->delete();
    }

    public function testJsonEncodingDoesNotExposeStoragePathOrSecurityContext(): void
    {
        $encoded = json_encode($this->upload($this->createStub(StorageInterface::class)), \JSON_THROW_ON_ERROR);

        self::assertStringNotContainsString('.tmp/completed', $encoded);
        self::assertStringNotContainsString('ownerId', $encoded);
        self::assertStringNotContainsString('tenantId', $encoded);
    }

    public function testExpiredUploadCannotBeRead(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(self::never())->method('read');
        $upload = new UploadedFile(
            '0123456789abcdef0123456789abcdef',
            'default',
            '.tmp/completed/1000000000-0123456789abcdef0123456789abcdef.txt',
            'document.txt',
            'text/plain',
            4,
            new \DateTimeImmutable('2000-01-01T00:00:00+00:00'),
            new \DateTimeImmutable('2001-01-01T00:00:00+00:00'),
            access: new UploadedFileAccess($storage),
        );

        $this->expectException(UploadException::class);
        $this->expectExceptionMessage('has expired');

        $upload->openStream();
    }

    public function testDetachedUploadCannotBeReadOrDeleted(): void
    {
        $upload = $this->detachedUpload();

        try {
            $upload->openStream();
            self::fail('A detached upload must not be readable.');
        } catch (UploadException $exception) {
            self::assertStringContainsString('detached', $exception->getMessage());
        }

        $this->expectException(UploadException::class);
        $this->expectExceptionMessage('detached');
        $upload->delete();
    }

    public function testWithMimeTypePreservesMetadataAndAccess(): void
    {
        $storage = $this->createStub(StorageInterface::class);
        $storage->method('read')->willReturn('data');
        $upload = $this->upload($storage)->withMimeType('text/csv');

        self::assertSame('text/csv', $upload->getMimeType());
        self::assertSame('document.txt', $upload->getClientOriginalName());
        $stream = $upload->openStream();
        self::assertSame('data', stream_get_contents($stream));
        fclose($stream);
    }

    public function testIsAnHttpFoundationUploadedFile(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(self::never())->method('read');
        $upload = $this->upload($storage);

        self::assertInstanceOf(HttpUploadedFile::class, $upload);
        self::assertTrue($upload->isValid());
        self::assertSame(\UPLOAD_ERR_OK, $upload->getError());
        self::assertSame('document.txt', $upload->getClientOriginalName());
        self::assertSame('txt', $upload->getClientOriginalExtension());
        self::assertSame('text/plain', $upload->getClientMimeType());
        self::assertSame('text/plain', $upload->getMimeType());
        self::assertSame(4, $upload->getSize());
    }

    public function testThePathIsLoadedFromStorageOnceWhenRequested(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(self::once())->method('read')->willReturn('data');
        $upload = $this->upload($storage);

        self::assertStringEqualsFile($upload->getPathname(), 'data');
        self::assertStringEqualsFile($upload->getRealPath(), 'data');
        self::assertStringEqualsFile((string) $upload, 'data');
        self::assertSame('data', $upload->getContent());
        self::assertSame('data', $upload->openFile()->fread(4));
    }

    public function testMoveWritesTheStoredBytesToTheTarget(): void
    {
        $storage = $this->createStub(StorageInterface::class);
        $storage->method('read')->willReturn('data');
        $directory = sys_get_temp_dir().'/ux_upload_move_'.bin2hex(random_bytes(4));

        try {
            $moved = $this->upload($storage)->move($directory, 'final.txt');

            self::assertSame($directory.\DIRECTORY_SEPARATOR.'final.txt', $moved->getPathname());
            self::assertStringEqualsFile($directory.'/final.txt', 'data');
        } finally {
            @unlink($directory.'/final.txt');
            @rmdir($directory);
        }
    }

    public function testMoveStreamsTheStoredBytesWithoutTheLocalCopy(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(self::once())->method('read')->willReturn('data');
        $upload = $this->upload($storage);
        $localPath = new \ReflectionMethod(\SplFileInfo::class, 'getPathname')->invoke($upload);
        $directory = sys_get_temp_dir().'/ux_upload_move_'.bin2hex(random_bytes(4));

        try {
            $upload->move($directory, 'final.txt');

            self::assertStringEqualsFile($directory.'/final.txt', 'data');
            self::assertStringEqualsFile($localPath, '');
        } finally {
            @unlink($directory.'/final.txt');
            @rmdir($directory);
        }
    }

    public function testMoveTakesTheLocalCopyOnceItExists(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(self::once())->method('read')->willReturn('data');
        $upload = $this->upload($storage);
        $localPath = $upload->getPathname();
        $directory = sys_get_temp_dir().'/ux_upload_move_'.bin2hex(random_bytes(4));

        try {
            $upload->move($directory, 'final.txt');

            self::assertStringEqualsFile($directory.'/final.txt', 'data');
            self::assertFileDoesNotExist($localPath);
        } finally {
            @unlink($directory.'/final.txt');
            @rmdir($directory);
        }
    }

    public function testTheLocalFileIsRemovedWithTheUpload(): void
    {
        $storage = $this->createStub(StorageInterface::class);
        $storage->method('read')->willReturn('data');
        $upload = $this->upload($storage);
        $path = $upload->getPathname();
        self::assertFileExists($path);

        unset($upload);

        self::assertFileDoesNotExist($path);
    }

    public function testACloneLoadsItsOwnLocalFile(): void
    {
        $storage = $this->createStub(StorageInterface::class);
        $storage->method('read')->willReturn('data');
        $upload = $this->upload($storage);
        $clone = clone $upload;
        $path = $clone->getPathname();

        unset($upload);

        self::assertStringEqualsFile($path, 'data');
    }

    public function testTwoValuesOfTheSameUploadAreEqual(): void
    {
        $upload = $this->detachedUpload();

        self::assertEquals($upload, $upload->withMimeType('text/plain'));
        self::assertNotEquals($upload, $upload->withMimeType('text/csv'));
    }

    public function testADetachedUploadHasNoLocalFile(): void
    {
        $this->expectException(UploadException::class);
        $this->expectExceptionMessage('detached');

        $this->detachedUpload()->getPathname();
    }

    public function testConstructorRejectsInvalidMetadata(): void
    {
        $cases = [
            ['', 'default', 'path', 'file', 'text/plain', 1, 'non-empty'],
            ['id', 'default', 'path', 'file', 'text/plain', -1, 'negative'],
        ];

        foreach ($cases as [$id, $uploader, $path, $name, $mime, $size, $message]) {
            try {
                new UploadedFile($id, $uploader, $path, $name, $mime, $size, new \DateTimeImmutable(), new \DateTimeImmutable('+1 hour'));
                self::fail('Invalid completed upload metadata must be rejected.');
            } catch (\Throwable $exception) {
                self::assertStringContainsString($message, $exception->getMessage());
            }
        }
    }

    public function testConstructorRejectsInvalidDatesAndPartialChecksum(): void
    {
        $now = new \DateTimeImmutable();
        try {
            new UploadedFile('id', 'default', 'path', 'file', 'text/plain', 1, $now, $now);
            self::fail('Expiration must be after creation.');
        } catch (\Throwable $exception) {
            self::assertStringContainsString('expire after', $exception->getMessage());
        }

        $this->expectException(\Throwable::class);
        $this->expectExceptionMessage('provided together');
        new UploadedFile('id', 'default', 'path', 'file', 'text/plain', 1, $now, $now->modify('+1 hour'), checksum: 'abc');
    }

    private function upload(StorageInterface $storage): UploadedFile
    {
        return new UploadedFile(
            '0123456789abcdef0123456789abcdef',
            'default',
            '.tmp/completed/2000000000-0123456789abcdef0123456789abcdef.txt',
            'document.txt',
            'text/plain',
            4,
            new \DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            new \DateTimeImmutable()->modify('+1 hour'),
            access: new UploadedFileAccess($storage),
        );
    }

    private function detachedUpload(): UploadedFile
    {
        return new UploadedFile(
            '0123456789abcdef0123456789abcdef',
            'default',
            '.tmp/completed/2000000000-0123456789abcdef0123456789abcdef.txt',
            'document.txt',
            'text/plain',
            4,
            new \DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            new \DateTimeImmutable()->modify('+1 hour'),
        );
    }
}
