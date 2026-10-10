<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Token;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\UX\Upload\Security\UploadContext;
use Symfony\UX\Upload\Storage\StorageInterface;
use Symfony\UX\Upload\Test\TestUploadContextResolver;
use Symfony\UX\Upload\Token\UploadTokenHandler;
use Symfony\UX\Upload\Upload\CompletedUpload;
use Symfony\UX\Upload\Upload\CompletedUploadAccess;

#[CoversClass(UploadTokenHandler::class)]
final class UploadTokenHandlerTest extends TestCase
{
    public function testResolveHydratesSignedMetadataWithoutReadingStorage(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(self::never())->method('read');
        $storage->expects(self::never())->method('exists');
        $handler = new UploadTokenHandler(new UriSigner('secret'), $storage);
        $upload = $this->upload(ownerId: 'user-1');

        $resolved = $handler->resolve(
            $handler->generate($upload, new UploadContext(ownerId: 'user-1')),
            new UploadContext(ownerId: 'user-1'),
        );

        self::assertNotNull($resolved);
        self::assertSame($upload->id, $resolved->id);
        self::assertSame($upload->getTemporaryPath(), $resolved->getTemporaryPath());
        self::assertSame($upload->expiresAt->getTimestamp(), $resolved->expiresAt->getTimestamp());
    }

    public function testResolveRejectsTamperingAndAnotherOwner(): void
    {
        $storage = $this->createStub(StorageInterface::class);
        $handler = new UploadTokenHandler(new UriSigner('secret'), $storage);
        $context = new UploadContext(ownerId: 'user-1');
        $token = $handler->generate($this->upload(ownerId: 'user-1'), $context);

        self::assertNull($handler->resolve($token.'tampered', $context));
        self::assertNull($handler->resolve($token, new UploadContext(ownerId: 'user-2')));
    }

    public function testLiveTargetReplacesFieldBindingButKeepsIdentityAndUploaderBinding(): void
    {
        $storage = $this->createStub(StorageInterface::class);
        $handler = new UploadTokenHandler(
            new UriSigner('secret'),
            $storage,
            contextResolver: new TestUploadContextResolver('user-1', 'tenant-1'),
        );
        $now = new \DateTimeImmutable()->setTimestamp(time());
        $upload = new CompletedUpload(
            '0123456789abcdef0123456789abcdef',
            'avatar',
            '.tmp/completed/'.($now->getTimestamp() + 3600).'-0123456789abcdef0123456789abcdef.jpg',
            'avatar.jpg',
            'image/jpeg',
            4,
            $now,
            $now->modify('+1 hour'),
            ownerId: 'user-1',
            tenantId: 'tenant-1',
            fieldName: 'profile.photo',
        );
        $token = $handler->generate($upload, new UploadContext('user-1', 'tenant-1', 'profile.photo'));

        self::assertNull($handler->resolve($token));
        self::assertNotNull($handler->resolveForLiveTarget($token, 'avatar'));
        self::assertNull($handler->resolveForLiveTarget($token, 'documents'));

        $otherOwner = new UploadTokenHandler(
            new UriSigner('secret'),
            $storage,
            contextResolver: new TestUploadContextResolver('user-2', 'tenant-1'),
        );
        self::assertNull($otherOwner->resolveForLiveTarget($token, 'avatar'));
    }

    public function testTokenExpiryDoesNotReplaceUploadExpiryMetadata(): void
    {
        $storage = $this->createStub(StorageInterface::class);
        $handler = new UploadTokenHandler(new UriSigner('secret'), $storage, ttl: 60);
        $upload = $this->upload();
        $token = $handler->generate($upload);
        parse_str($token, $payload);

        self::assertSame($upload->expiresAt->getTimestamp(), (int) $payload['e']);
        self::assertGreaterThanOrEqual(time() + 58, (int) $payload['x']);
        self::assertLessThanOrEqual(time() + 60, (int) $payload['x']);
    }

    public function testRejectsUnsafeCompletedPrefix(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new UploadTokenHandler(
            new UriSigner('secret'),
            $this->createStub(StorageInterface::class),
            completedPrefix: '../completed',
        );
    }

    public function testRejectsNonPositiveTokenTtl(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('greater than zero');

        new UploadTokenHandler(new UriSigner('secret'), $this->createStub(StorageInterface::class), ttl: 0);
    }

    public function testGenerateRejectsAnotherContextAndUnsafePath(): void
    {
        $handler = new UploadTokenHandler(new UriSigner('secret'), $this->createStub(StorageInterface::class));

        try {
            $handler->generate($this->upload(ownerId: 'user-1'), new UploadContext(ownerId: 'user-2'));
            self::fail('Another owner must not receive a token.');
        } catch (\Throwable $exception) {
            self::assertStringContainsString('another upload context', $exception->getMessage());
        }

        $now = new \DateTimeImmutable();
        $unsafe = new CompletedUpload(
            'id',
            'default',
            'application-owned/file.txt',
            'file.txt',
            'text/plain',
            1,
            $now,
            $now->modify('+1 hour'),
        );

        $this->expectException(\Throwable::class);
        $this->expectExceptionMessage('outside UX Upload');
        $handler->generate($unsafe);
    }

    public function testAcceptsPrebuiltCompletedUploadAccess(): void
    {
        $storage = $this->createStub(StorageInterface::class);
        $handler = new UploadTokenHandler(new UriSigner('secret'), new CompletedUploadAccess($storage));

        self::assertNotNull($handler->resolve($handler->generate($this->upload())));
    }

    public function testResolveRejectsOversizedStructuredAndIncompleteTokens(): void
    {
        $signer = new UriSigner('secret');
        $handler = new UploadTokenHandler($signer, $this->createStub(StorageInterface::class));

        self::assertNull($handler->resolve(str_repeat('a', 8193)));
        self::assertNull($handler->resolve($this->signQuery($signer, 'i[]=nested')));
        self::assertNull($handler->resolve($this->signPayload($signer, ['i' => 'only-id'])));
    }

    public function testResolveRejectsExpiredUnsafeAndPartialChecksumPayloads(): void
    {
        $signer = new UriSigner('secret');
        $handler = new UploadTokenHandler($signer, $this->createStub(StorageInterface::class));
        $valid = $this->validPayload();

        self::assertNull($handler->resolve($this->signPayload($signer, [...$valid, 'e' => (string) (time() - 1)])));
        self::assertNull($handler->resolve($this->signPayload($signer, [...$valid, 'p' => '../outside.txt'])));
        self::assertNull($handler->resolve($this->signPayload($signer, [...$valid, 'h' => 'checksum'])));
    }

    public function testResolveRejectsMetadataThatCannotBuildACompletedUpload(): void
    {
        $signer = new UriSigner('secret');
        $handler = new UploadTokenHandler($signer, $this->createStub(StorageInterface::class));
        $valid = $this->validPayload();

        self::assertNull($handler->resolve($this->signPayload($signer, [
            ...$valid,
            'c' => (string) (time() + 7200),
            'e' => (string) (time() + 3600),
        ])));
    }

    private function upload(?string $ownerId = null): CompletedUpload
    {
        $now = new \DateTimeImmutable()->setTimestamp(time());

        return new CompletedUpload(
            '0123456789abcdef0123456789abcdef',
            'default',
            '.tmp/completed/'.($now->getTimestamp() + 3600).'-0123456789abcdef0123456789abcdef.txt',
            'document.txt',
            'text/plain',
            4,
            $now,
            $now->modify('+1 hour'),
            checksum: hash('sha256', 'data'),
            checksumAlgorithm: 'sha256',
            ownerId: $ownerId,
        );
    }

    /**
     * @return array<string, string>
     */
    private function validPayload(): array
    {
        return [
            'i' => str_repeat('a', 32),
            'u' => 'default',
            'p' => '.tmp/completed/'.(time() + 3600).'-'.str_repeat('a', 32).'.txt',
            'f' => 'file.txt',
            'm' => 'text/plain',
            's' => '4',
            'c' => (string) time(),
            'e' => (string) (time() + 3600),
            'x' => (string) (time() + 3600),
        ];
    }

    /**
     * @param array<string, string> $payload
     */
    private function signPayload(UriSigner $signer, array $payload): string
    {
        return $this->signQuery($signer, http_build_query($payload));
    }

    private function signQuery(UriSigner $signer, string $query): string
    {
        return substr($signer->sign('?'.$query), 1);
    }
}
