<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Upload;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Exception\Upload\InvalidSignatureException;
use Symfony\UX\Editor\Upload\SignedUploadUrlGenerator;

final class SignedUploadUrlGeneratorTest extends TestCase
{
    public function testHappyRoundTrip(): void
    {
        $g = new SignedUploadUrlGenerator(secret: 's3cret', ttlSeconds: 3600);
        $token = $g->sign(field: 'body', profile: 'default');
        $g->verify($token, field: 'body', profile: 'default');
        $this->addToAssertionCount(1);
    }

    public function testTamperedTokenThrows(): void
    {
        $g = new SignedUploadUrlGenerator('s3cret', 3600);
        $token = $g->sign('body', 'default');
        $this->expectException(InvalidSignatureException::class);
        $g->verify($token.'x', 'body', 'default');
    }

    public function testFieldMismatchThrows(): void
    {
        $g = new SignedUploadUrlGenerator('s3cret', 3600);
        $token = $g->sign('body', 'default');
        $this->expectException(InvalidSignatureException::class);
        $g->verify($token, 'other-field', 'default');
    }

    public function testExpiredTokenThrows(): void
    {
        $g = new SignedUploadUrlGenerator('s3cret', ttlSeconds: -1);
        $token = $g->sign('body', 'default');
        $this->expectException(InvalidSignatureException::class);
        $g->verify($token, 'body', 'default');
    }
}
