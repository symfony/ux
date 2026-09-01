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

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\UX\Upload\Security\UploadContext;
use Symfony\UX\Upload\Token\ResumeTokenHandler;

final class ResumeTokenHandlerTest extends TestCase
{
    public function testTokenIsBoundToOwnerAndExpiry()
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
}
