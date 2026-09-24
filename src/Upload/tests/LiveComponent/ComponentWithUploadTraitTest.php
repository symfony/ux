<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\LiveComponent;

use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\Upload\LiveComponent\Attribute\UploadTarget;
use Symfony\UX\Upload\LiveComponent\ComponentWithUploadTrait;
use Symfony\UX\Upload\Security\UploadContext;
use Symfony\UX\Upload\Storage\InMemoryStorage;
use Symfony\UX\Upload\Test\TestUploadContextResolver;
use Symfony\UX\Upload\Test\UploadedFileFactory;
use Symfony\UX\Upload\Token\UploadTokenHandler;
use Symfony\UX\Upload\Upload\UploadedFile;

#[CoversTrait(ComponentWithUploadTrait::class)]
final class ComponentWithUploadTraitTest extends TestCase
{
    public function testResolveAndClearDeletesCompletedTemporaryUpload(): void
    {
        $storage = new InMemoryStorage();
        $upload = new UploadedFileFactory(
            uploader: 'avatar',
            ownerId: 'user-1',
            tenantId: 'tenant-1',
            fieldName: 'profile.photo',
        )->create($storage);
        $handler = new UploadTokenHandler(
            new UriSigner('secret'),
            $storage,
            contextResolver: new TestUploadContextResolver('user-1', 'tenant-1'),
        );
        $component = new class {
            use ComponentWithUploadTrait;

            #[LiveProp]
            #[UploadTarget(uploader: 'avatar')]
            private ?string $file = null;

            public function upload(): ?UploadedFile
            {
                return $this->getUpload('file');
            }
        };
        $component->setUploadTokenHandler($handler);

        $component->applyUpload('file', $handler->generate($upload, new UploadContext('user-1', 'tenant-1', 'profile.photo')));
        self::assertSame($upload->getId(), $component->upload()?->getId());
        $component->clearUpload('file');
        self::assertNull($component->upload());
        self::assertFalse($storage->exists($upload->getStoragePath()));
    }

    public function testUploaderRestrictionRejectsAValidTokenFromAnotherUploader(): void
    {
        $storage = new InMemoryStorage();
        $upload = new UploadedFileFactory(uploader: 'documents', fieldName: 'profile.photo')->create($storage);
        $handler = new UploadTokenHandler(new UriSigner('secret'), $storage);
        $component = new class {
            use ComponentWithUploadTrait;

            #[LiveProp]
            #[UploadTarget(uploader: 'avatar')]
            private ?string $file = null;

            public function upload(): ?UploadedFile
            {
                return $this->getUpload('file');
            }
        };
        $component->setUploadTokenHandler($handler);

        $component->applyUpload('file', $handler->generate($upload, new UploadContext(fieldName: 'profile.photo')));

        self::assertNull($component->upload());
    }

    public function testTargetMustAlsoBeANullableStringLiveProp(): void
    {
        $handler = new UploadTokenHandler(new UriSigner('secret'), new InMemoryStorage());
        $component = new class {
            use ComponentWithUploadTrait;

            #[UploadTarget]
            private string $file = '';
        };
        $component->setUploadTokenHandler($handler);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('must have the "?string" type');

        $component->applyUpload('file', 'invalid');
    }

    public function testTargetMustAlsoBeALiveProp(): void
    {
        $handler = new UploadTokenHandler(new UriSigner('secret'), new InMemoryStorage());
        $component = new class {
            use ComponentWithUploadTrait;

            #[UploadTarget]
            private ?string $file = null;
        };
        $component->setUploadTokenHandler($handler);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('must also carry');

        $component->applyUpload('file', 'invalid');
    }

    public function testNonTargetPropertyCannotBeWritten(): void
    {
        $handler = new UploadTokenHandler(new UriSigner('secret'), new InMemoryStorage());
        $component = new class {
            use ComponentWithUploadTrait;

            #[LiveProp]
            private ?string $file = null;
        };
        $component->setUploadTokenHandler($handler);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('is not an upload target');

        $component->applyUpload('file', 'invalid');
    }
}
