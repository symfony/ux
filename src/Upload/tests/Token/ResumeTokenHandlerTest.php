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
use Symfony\UX\Upload\Token\ResumeTokenHandler;

#[CoversClass(ResumeTokenHandler::class)]
final class ResumeTokenHandlerTest extends TestCase
{
    public function testTokenIsBoundToOwnerAndExpiry(): void
    {
        $handler = new ResumeTokenHandler(new UriSigner('secret'));
        $token = $handler->generate('upload-1', new UploadContext('owner-a', 'tenant-a', 'field-a'));

        self::assertSame('upload-1', $handler->resolve($token, new UploadContext('owner-a', 'tenant-a', 'field-a')));
        self::assertNull($handler->resolve($token, new UploadContext('owner-b', 'tenant-a', 'field-a')));
        self::assertNull($handler->resolve($token, new UploadContext('owner-a', 'tenant-b', 'field-a')));
        self::assertNull($handler->resolve($token, new UploadContext('owner-a', 'tenant-a', 'field-b')));
        self::assertNull(new ResumeTokenHandler(new UriSigner('secret'), -1)->resolve(
            new ResumeTokenHandler(new UriSigner('secret'), -1)->generate('upload-1', new UploadContext()),
            new UploadContext(),
        ));
        self::assertNull($handler->resolve('', new UploadContext()));
        self::assertNull($handler->resolve(str_repeat('a', 4097), new UploadContext()));
    }

    public function testResolveRejectsAValidPayloadSignedAsAnotherTokenKind(): void
    {
        $signer = new UriSigner('secret');
        $handler = new ResumeTokenHandler($signer);
        $context = new UploadContext('owner-a', 'tenant-a', 'field-a');
        $expiresAt = time() + 3600;
        $sign = static fn (array $payload): string => substr($signer->sign('?'.http_build_query($payload), $expiresAt), 1);
        $payload = ['i' => 'upload-1', 'o' => 'owner-a', 't' => 'tenant-a', 'f' => 'field-a', 'e' => $expiresAt];

        self::assertSame('upload-1', $handler->resolve($sign(['k' => 'r'] + $payload), $context));
        self::assertNull($handler->resolve($sign(['k' => 'u'] + $payload), $context));
        self::assertNull($handler->resolve($sign(['k' => 'p'] + $payload), $context));
        self::assertNull($handler->resolve($sign($payload), $context));
    }
}
