<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\EventListener;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Mime\MimeTypes;
use Symfony\UX\Upload\Event\UploadAssembledEvent;
use Symfony\UX\Upload\EventListener\FileValidationListener;
use Symfony\UX\Upload\Exception\ValidationException;
use Symfony\UX\Upload\Storage\InMemoryStorage;
use Symfony\UX\Upload\Storage\StorageInterface;
use Symfony\UX\Upload\Upload\CompletedUpload;
use Symfony\UX\Upload\Upload\CompletedUploadAccess;
use Symfony\UX\Upload\UploaderInterface;

final class FileValidationListenerTest extends TestCase
{
    #[DataProvider('ambiguousTextFiles')]
    public function testAmbiguousTextDetectionUsesAnExtensionCompatibleAllowedType(string $filename, string $declaredMimeType, string $content)
    {
        $storage = new InMemoryStorage();
        $path = '.tmp/completed/'.(time() + 3600).'-'.str_repeat('a', 32).'.'.pathinfo($filename, \PATHINFO_EXTENSION);
        $storage->write($path, $content);
        $now = new \DateTimeImmutable();
        $event = new UploadAssembledEvent(new CompletedUpload(
            id: str_repeat('a', 32),
            uploader: 'default',
            path: $path,
            originalName: $filename,
            mimeType: $declaredMimeType,
            size: \strlen($content),
            createdAt: $now,
            expiresAt: $now->modify('+1 hour'),
            access: new CompletedUploadAccess($storage),
        ), [
            'policyMaxSize' => 1024,
            'policyAllowedTypes' => [$declaredMimeType],
        ]);
        $listener = new FileValidationListener(
            MimeTypes::getDefault(),
            new ServiceLocator([]),
        );

        $listener($event);

        self::assertSame($declaredMimeType, $event->getUpload()->getMimeType());
    }

    public static function ambiguousTextFiles(): iterable
    {
        yield 'CSV detected as text/plain' => ['report.csv', 'text/csv', "name,total\nA,1\n"];
        yield 'Markdown detected as text/plain' => ['README.md', 'text/markdown', "# Title\n\nText\n"];
    }

    public function testPlainTextCannotBecomePdfFromFilenameAndClientDeclaration()
    {
        $event = $this->event(new InMemoryStorage(), 'This is not a PDF.', 'invoice.pdf', 'application/pdf');

        try {
            $this->listener(allowedTypes: ['application/pdf'])($event);
            self::fail('A binary MIME type must not be inferred from a filename.');
        } catch (ValidationException $exception) {
            self::assertStringContainsString('text/plain', $exception->getMessage());
        }

        self::assertSame('application/pdf', $event->getUpload()->getMimeType());
    }

    public function testOctetStreamCannotBecomeImageFromFilenameAndClientDeclaration()
    {
        $content = "\x00\x01\x02\x03\x04\x05";
        $event = $this->event(new InMemoryStorage(), $content, 'avatar.png', 'image/png');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('application/octet-stream');

        $this->listener(allowedTypes: ['image/png'])($event);
    }

    public function testDetectedPlainTextWinsOverBinaryDeclarationWithoutAllowList()
    {
        $event = $this->event(new InMemoryStorage(), 'This is not a PDF.', 'invoice.pdf', 'application/pdf');

        $this->listener()($event);

        self::assertSame('text/plain', $event->getUpload()->getMimeType());
    }

    public function testPolicySizeLimitRejectsBeforeReadingStorage()
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(self::never())->method('read');
        $event = $this->event($storage, 'data', size: 5, metadata: [
            'policyMaxSize' => 4,
            'policyAllowedTypes' => [],
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('exceeds maximum');
        $this->listener()($event);
    }

    public function testStorageInspectionFailureIsLoggedAndWrapped()
    {
        $storage = $this->createStub(StorageInterface::class);
        $storage->method('read')->willThrowException(new \RuntimeException('backend down'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with('Unable to inspect the assembled upload MIME type.', self::callback(
                static fn (array $context): bool => 'backend down' === $context['exception']->getMessage() && str_repeat('b', 32) === $context['uploadId'],
            ));

        try {
            $this->listener(logger: $logger)($this->event($storage, ''));
            self::fail('Storage inspection failures must reject completion.');
        } catch (ValidationException $exception) {
            self::assertStringContainsString('Unable to inspect', $exception->getMessage());
            self::assertSame('backend down', $exception->getPrevious()?->getMessage());
        }
    }

    public function testEmptyContentCannotProduceAMimeType()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unable to determine');
        $this->listener()($this->event(new InMemoryStorage(), ''));
    }

    public function testDetectedContentWinsOverSpoofedDeclarationWithoutAllowList()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');
        $content = file_get_contents(__DIR__.'/../Fixtures/files/valid_image.png');
        self::assertIsString($content);
        $event = $this->event(new InMemoryStorage(), $content, 'image.png', 'application/pdf');

        $this->listener(logger: $logger)($event);

        self::assertSame('image/png', $event->getUpload()->getMimeType());
    }

    public function testWildcardAllowListAcceptsDetectedContent()
    {
        $content = file_get_contents(__DIR__.'/../Fixtures/files/valid_image.png');
        self::assertIsString($content);
        $event = $this->event(new InMemoryStorage(), $content, 'image.png', 'image/png');

        $this->listener(allowedTypes: ['image/*'])($event);

        self::assertSame('image/png', $event->getUpload()->getMimeType());
    }

    public function testDisallowedDetectedContentIsRejected()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('is not allowed');
        $this->listener(allowedTypes: ['application/pdf'])(
            $this->event(new InMemoryStorage(), 'plain text', 'notes.txt', 'text/plain'),
        );
    }

    public function testNamedUploaderConstraintsAreResolvedFromTheLocator()
    {
        $uploader = $this->createStub(UploaderInterface::class);
        $uploader->method('getConfig')->willReturn([
            'max_size' => 3,
            'allowed_types' => ['text/plain'],
            'chunk_size' => 64,
            'integrity_algorithm' => 'sha256',
        ]);
        $listener = new FileValidationListener(
            MimeTypes::getDefault(),
            new ServiceLocator(['documents' => static fn (): UploaderInterface => $uploader]),
        );
        $event = $this->event(new InMemoryStorage(), 'data', size: 4, metadata: ['uploader' => 'documents']);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('3 bytes');
        $listener($event);
    }

    public function testRejectsInvalidNamedUploaderService()
    {
        $listener = new FileValidationListener(
            MimeTypes::getDefault(),
            new ServiceLocator(['documents' => static fn (): object => new \stdClass()]),
        );
        $event = $this->event(new InMemoryStorage(), 'data', metadata: ['uploader' => 'documents']);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('must implement');

        $listener($event);
    }

    /**
     * @param list<string> $allowedTypes
     */
    private function listener(array $allowedTypes = [], int $maxSize = 0, ?LoggerInterface $logger = null): FileValidationListener
    {
        return new FileValidationListener(
            MimeTypes::getDefault(),
            new ServiceLocator([]),
            $allowedTypes,
            $maxSize,
            $logger,
        );
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function event(
        StorageInterface $storage,
        string $content,
        string $filename = 'notes.txt',
        string $mimeType = 'text/plain',
        ?int $size = null,
        array $metadata = [],
    ): UploadAssembledEvent {
        $id = str_repeat('b', 32);
        $path = '.tmp/completed/'.(time() + 3600).'-'.$id.'.'.pathinfo($filename, \PATHINFO_EXTENSION);
        if ($storage instanceof InMemoryStorage) {
            $storage->write($path, $content);
        }
        $now = new \DateTimeImmutable();

        return new UploadAssembledEvent(new CompletedUpload(
            id: $id,
            uploader: 'default',
            path: $path,
            originalName: $filename,
            mimeType: $mimeType,
            size: $size ?? \strlen($content),
            createdAt: $now,
            expiresAt: $now->modify('+1 hour'),
            access: new CompletedUploadAccess($storage),
        ), $metadata);
    }
}
