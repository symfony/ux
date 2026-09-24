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

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Upload\Form\UploadType;
use Symfony\UX\Upload\Security\UploadContext;
use Symfony\UX\Upload\Storage\InMemoryStorage;
use Symfony\UX\Upload\Token\UploadTokenHandler;
use Symfony\UX\Upload\Upload\UploadedFile;
use Symfony\UX\Upload\Uploader;
use Symfony\UX\Upload\Url\UploadUrlGeneratorInterface;

#[CoversNothing]
final class UploadedFileGoldenPathTest extends TestCase
{
    public function testAssemblyFormRoundTripAndLazyApplicationRead(): void
    {
        $storage = new InMemoryStorage();
        $dispatcher = new EventDispatcher();
        $urlGenerator = $this->createStub(UploadUrlGeneratorInterface::class);
        $urlGenerator->method('generateUploadUrl')->willReturn('/upload/id');
        $uploader = new Uploader($storage, $urlGenerator, $dispatcher);

        $context = new UploadContext(fieldName: 'ux_upload');
        $pending = $uploader->initializeUpload('document.txt', 4, 'text/plain', context: $context);
        $uploader->storeChunk($pending->uploadId, 0, 'data');
        $completed = $uploader->completeUpload($pending->uploadId);

        self::assertInstanceOf(UploadedFile::class, $completed);
        self::assertStringStartsWith('.tmp/completed/', $completed->getStoragePath());

        $handler = new UploadTokenHandler(new UriSigner('secret'), $storage);
        $token = $handler->generate($completed, $context);
        $router = $this->createStub(UrlGeneratorInterface::class);
        $router->method('generate')->willReturnCallback(static fn (string $route): string => '/upload/'.('ux_upload_remove' === $route ? 'remove' : 'init'));
        $type = new UploadType($router, $handler);
        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new PreloadedExtension([$type], []))
            ->getFormFactory();
        $form = $factory->createNamed('ux_upload', UploadType::class);
        $form->submit(json_encode(['token' => $token, 'meta' => []], \JSON_THROW_ON_ERROR));

        self::assertTrue($form->isSynchronized(), $form->getTransformationFailure()?->getMessage() ?? '');
        self::assertInstanceOf(UploadedFile::class, $form->getData());
        self::assertSame($completed->getId(), $form->getData()->getId());

        $stream = $form->getData()->openStream();
        try {
            self::assertSame('data', stream_get_contents($stream));
        } finally {
            fclose($stream);
        }
    }
}
