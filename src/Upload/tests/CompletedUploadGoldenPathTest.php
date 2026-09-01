<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Upload\Event\UploadAssembledEvent;
use Symfony\UX\Upload\Exception\ValidationException;
use Symfony\UX\Upload\Form\FileUploadType;
use Symfony\UX\Upload\Security\UploadContext;
use Symfony\UX\Upload\Tests\Mock\MockStorage;
use Symfony\UX\Upload\Token\UploadTokenHandler;
use Symfony\UX\Upload\Upload\CompletedUpload;
use Symfony\UX\Upload\Uploader;
use Symfony\UX\Upload\Url\UploadUrlGeneratorInterface;

final class CompletedUploadGoldenPathTest extends TestCase
{
    public function testAssemblyFormRoundTripAndLazyApplicationRead()
    {
        $storage = new MockStorage();
        $dispatcher = new EventDispatcher();
        $urlGenerator = $this->createStub(UploadUrlGeneratorInterface::class);
        $urlGenerator->method('generateUploadUrl')->willReturn('/upload/id');
        $uploader = new Uploader($storage, $urlGenerator, $dispatcher);

        $context = new UploadContext(fieldName: 'ux_upload');
        $pending = $uploader->initializeUpload('document.txt', 4, 'text/plain', context: $context);
        $uploader->storeChunk($pending->uploadId, 0, 'data');
        $completed = $uploader->completeUpload($pending->uploadId);

        self::assertInstanceOf(CompletedUpload::class, $completed);
        self::assertStringStartsWith('.tmp/completed/', $completed->getTemporaryPath());

        $handler = new UploadTokenHandler(new UriSigner('secret'), $storage);
        $token = $handler->generate($completed, $context);
        $router = $this->createStub(UrlGeneratorInterface::class);
        $router->method('generate')->willReturnCallback(static fn (string $route): string => '/upload/'.('ux_upload_remove' === $route ? 'remove' : 'init'));
        $type = new FileUploadType($router, $handler);
        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new PreloadedExtension([$type], []))
            ->getFormFactory();
        $form = $factory->createNamed('ux_upload', FileUploadType::class);
        $form->submit(json_encode(['token' => $token, 'meta' => []], \JSON_THROW_ON_ERROR));

        self::assertTrue($form->isSynchronized(), $form->getTransformationFailure()?->getMessage() ?? '');
        self::assertInstanceOf(CompletedUpload::class, $form->getData());
        self::assertSame($completed->id, $form->getData()->id);

        $stream = $form->getData()->openStream();
        try {
            self::assertSame('data', stream_get_contents($stream));
        } finally {
            fclose($stream);
        }
    }

    public function testTechnicalValidationRunsBeforeCompletionReturns()
    {
        $storage = new MockStorage();
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(UploadAssembledEvent::class, static function (): never {
            throw new ValidationException('technical validation failed');
        });
        $urlGenerator = $this->createStub(UploadUrlGeneratorInterface::class);
        $urlGenerator->method('generateUploadUrl')->willReturn('/upload/id');
        $uploader = new Uploader($storage, $urlGenerator, $dispatcher);
        $pending = $uploader->initializeUpload('invalid.txt', 4, 'text/plain');
        $uploader->storeChunk($pending->uploadId, 0, 'data');

        try {
            $uploader->completeUpload($pending->uploadId);
            self::fail('Validation must abort completion.');
        } catch (ValidationException $e) {
            self::assertSame('technical validation failed', $e->getMessage());
        }

        self::assertNull($storage->getMetadata($pending->uploadId));
    }

    public function testTransientCompletionFailureCanRetryWithoutRetransmittingChunks()
    {
        $storage = new MockStorage();
        $dispatcher = new EventDispatcher();
        $attempt = 0;
        $dispatcher->addListener(UploadAssembledEvent::class, static function () use (&$attempt): void {
            if (1 === ++$attempt) {
                throw new \RuntimeException('scanner unavailable');
            }
        });
        $urlGenerator = $this->createStub(UploadUrlGeneratorInterface::class);
        $urlGenerator->method('generateUploadUrl')->willReturn('/upload/id');
        $uploader = new Uploader($storage, $urlGenerator, $dispatcher);
        $pending = $uploader->initializeUpload('retry.txt', 4, 'text/plain');
        $uploader->storeChunk($pending->uploadId, 0, 'data');

        try {
            $uploader->completeUpload($pending->uploadId);
            self::fail('The first completion must fail.');
        } catch (\RuntimeException $e) {
            self::assertSame('scanner unavailable', $e->getMessage());
        }
        self::assertNotNull($storage->getMetadata($pending->uploadId));
        self::assertSame([0], $storage->listChunks($pending->uploadId));

        $completed = $uploader->completeUpload($pending->uploadId);
        self::assertSame('retry.txt', $completed->getOriginalName());
    }
}
