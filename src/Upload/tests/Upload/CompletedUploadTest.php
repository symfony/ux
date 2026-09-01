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
use Symfony\UX\Upload\Upload\CompletedUpload;
use Symfony\UX\Upload\Upload\CompletedUploadAccess;

#[CoversClass(CompletedUpload::class)]
final class CompletedUploadTest extends TestCase
{
    public function testMetadataAccessDoesNotReadStorage()
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(self::never())->method('read');
        $upload = $this->upload($storage);

        self::assertSame('document.txt', $upload->getOriginalName());
        self::assertSame('text/plain', $upload->getMimeType());
        self::assertSame(4, $upload->getSize());
        self::assertSame('.tmp/completed/2000000000-0123456789abcdef0123456789abcdef.txt', $upload->getTemporaryPath());
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

    public function testOpenStreamPerformsTheExplicitRead()
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

    public function testDeleteRemovesOnlyTheTemporaryObject()
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

    public function testJsonEncodingDoesNotExposeStoragePathOrSecurityContext()
    {
        $encoded = json_encode($this->upload($this->createStub(StorageInterface::class)), \JSON_THROW_ON_ERROR);

        self::assertStringNotContainsString('.tmp/completed', $encoded);
        self::assertStringNotContainsString('ownerId', $encoded);
        self::assertStringNotContainsString('tenantId', $encoded);
    }

    public function testExpiredUploadCannotBeRead()
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(self::never())->method('read');
        $upload = new CompletedUpload(
            '0123456789abcdef0123456789abcdef',
            'default',
            '.tmp/completed/1000000000-0123456789abcdef0123456789abcdef.txt',
            'document.txt',
            'text/plain',
            4,
            new \DateTimeImmutable('2000-01-01T00:00:00+00:00'),
            new \DateTimeImmutable('2001-01-01T00:00:00+00:00'),
            access: new CompletedUploadAccess($storage),
        );

        $this->expectException(UploadException::class);
        $this->expectExceptionMessage('has expired');

        $upload->openStream();
    }

    public function testDetachedUploadCannotBeReadOrDeleted()
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

    public function testAccessReturnsExistingStreamsAndRejectsUnsupportedStorageContent()
    {
        $stream = fopen('php://temp', 'w+');
        self::assertIsResource($stream);
        $storage = $this->createStub(StorageInterface::class);
        $storage->method('read')->willReturn($stream);
        self::assertSame($stream, new CompletedUploadAccess($storage)->openStream('path'));
        fclose($stream);

        $invalidStorage = $this->createStub(StorageInterface::class);
        $invalidStorage->method('read')->willReturn(123);
        $this->expectException(UploadException::class);
        $this->expectExceptionMessage('unsupported content');
        new CompletedUploadAccess($invalidStorage)->openStream('path');
    }

    public function testWithMimeTypePreservesMetadataAndAccess()
    {
        $storage = $this->createStub(StorageInterface::class);
        $storage->method('read')->willReturn('data');
        $upload = $this->upload($storage)->withMimeType('text/csv');

        self::assertSame('text/csv', $upload->getMimeType());
        self::assertSame('document.txt', $upload->getOriginalName());
        $stream = $upload->openStream();
        self::assertSame('data', stream_get_contents($stream));
        fclose($stream);
    }

    public function testConstructorRejectsInvalidMetadata()
    {
        $cases = [
            ['', 'default', 'path', 'file', 'text/plain', 1, 'non-empty'],
            ['id', 'default', 'path', 'file', 'text/plain', -1, 'negative'],
        ];

        foreach ($cases as [$id, $uploader, $path, $name, $mime, $size, $message]) {
            try {
                new CompletedUpload($id, $uploader, $path, $name, $mime, $size, new \DateTimeImmutable(), new \DateTimeImmutable('+1 hour'));
                self::fail('Invalid completed upload metadata must be rejected.');
            } catch (\Throwable $exception) {
                self::assertStringContainsString($message, $exception->getMessage());
            }
        }
    }

    public function testConstructorRejectsInvalidDatesAndPartialChecksum()
    {
        $now = new \DateTimeImmutable();
        try {
            new CompletedUpload('id', 'default', 'path', 'file', 'text/plain', 1, $now, $now);
            self::fail('Expiration must be after creation.');
        } catch (\Throwable $exception) {
            self::assertStringContainsString('expire after', $exception->getMessage());
        }

        $this->expectException(\Throwable::class);
        $this->expectExceptionMessage('provided together');
        new CompletedUpload('id', 'default', 'path', 'file', 'text/plain', 1, $now, $now->modify('+1 hour'), checksum: 'abc');
    }

    private function upload(StorageInterface $storage): CompletedUpload
    {
        return new CompletedUpload(
            '0123456789abcdef0123456789abcdef',
            'default',
            '.tmp/completed/2000000000-0123456789abcdef0123456789abcdef.txt',
            'document.txt',
            'text/plain',
            4,
            new \DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            new \DateTimeImmutable()->modify('+1 hour'),
            access: new CompletedUploadAccess($storage),
        );
    }

    private function detachedUpload(): CompletedUpload
    {
        return new CompletedUpload(
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
