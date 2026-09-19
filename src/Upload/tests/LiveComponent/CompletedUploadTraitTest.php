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

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\Upload\LiveComponent\Attribute\UploadTarget;
use Symfony\UX\Upload\LiveComponent\ComponentWithUploadTrait;
use Symfony\UX\Upload\Security\UploadContext;
use Symfony\UX\Upload\Test\TestUploadContextResolver;
use Symfony\UX\Upload\Tests\Mock\MockStorage;
use Symfony\UX\Upload\Token\UploadTokenHandler;
use Symfony\UX\Upload\Upload\CompletedUpload;

final class CompletedUploadTraitTest extends TestCase
{
    public function testTargetRejectsAnEmptyUploaderName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be empty');

        new UploadTarget('');
    }

    public function testResolveAndClearDeletesCompletedTemporaryUpload()
    {
        $storage = new MockStorage();
        $now = new \DateTimeImmutable();
        $upload = new CompletedUpload(
            '0123456789abcdef0123456789abcdef',
            'avatar',
            '.tmp/completed/'.($now->getTimestamp() + 3600).'-0123456789abcdef0123456789abcdef.txt',
            'file.txt',
            'text/plain',
            4,
            $now,
            $now->modify('+1 hour'),
            ownerId: 'user-1',
            tenantId: 'tenant-1',
            fieldName: 'profile.photo',
            access: new \Symfony\UX\Upload\Upload\CompletedUploadAccess($storage),
        );
        $storage->write($upload->getTemporaryPath(), 'data');
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

            public function upload(): ?CompletedUpload
            {
                return $this->getUpload('file');
            }
        };
        $component->setUploadTokenHandler($handler);

        $component->applyUpload('file', $handler->generate($upload, new UploadContext('user-1', 'tenant-1', 'profile.photo')));
        self::assertSame($upload->id, $component->upload()?->id);
        $component->clearUpload('file');
        self::assertNull($component->upload());
        self::assertFalse($storage->exists($upload->getTemporaryPath()));
    }

    public function testUploaderRestrictionRejectsAValidTokenFromAnotherUploader()
    {
        $storage = new MockStorage();
        $now = new \DateTimeImmutable();
        $upload = new CompletedUpload(
            '0123456789abcdef0123456789abcdef',
            'documents',
            '.tmp/completed/'.($now->getTimestamp() + 3600).'-0123456789abcdef0123456789abcdef.txt',
            'file.txt',
            'text/plain',
            4,
            $now,
            $now->modify('+1 hour'),
            fieldName: 'profile.photo',
            access: new \Symfony\UX\Upload\Upload\CompletedUploadAccess($storage),
        );
        $handler = new UploadTokenHandler(new UriSigner('secret'), $storage);
        $component = new class {
            use ComponentWithUploadTrait;

            #[LiveProp]
            #[UploadTarget(uploader: 'avatar')]
            private ?string $file = null;

            public function upload(): ?CompletedUpload
            {
                return $this->getUpload('file');
            }
        };
        $component->setUploadTokenHandler($handler);

        $component->applyUpload('file', $handler->generate($upload, new UploadContext(fieldName: 'profile.photo')));

        self::assertNull($component->upload());
    }

    public function testTargetMustAlsoBeANullableStringLiveProp()
    {
        $handler = new UploadTokenHandler(new UriSigner('secret'), new MockStorage());
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

    public function testTargetMustAlsoBeALiveProp()
    {
        $handler = new UploadTokenHandler(new UriSigner('secret'), new MockStorage());
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

    public function testNonTargetPropertyCannotBeWritten()
    {
        $handler = new UploadTokenHandler(new UriSigner('secret'), new MockStorage());
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
