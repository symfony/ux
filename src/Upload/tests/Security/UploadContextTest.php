<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Security;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Upload\Security\UploadContext;

final class UploadContextTest extends TestCase
{
    public function testAccessorsMatchingAndFingerprint()
    {
        $context = new UploadContext('user-1', 'tenant-1', 'profile.avatar');

        self::assertSame('user-1', $context->getOwnerId());
        self::assertSame('tenant-1', $context->getTenantId());
        self::assertSame('profile.avatar', $context->getFieldName());
        self::assertTrue($context->matches('user-1', 'tenant-1', 'profile.avatar'));
        self::assertFalse($context->matches('user-2', 'tenant-1', 'profile.avatar'));
        self::assertSame($context->fingerprint(), new UploadContext('user-1', 'tenant-1', 'profile.avatar')->fingerprint());
        self::assertNotSame($context->fingerprint(), new UploadContext('user-1', 'tenant-1', 'profile.cover')->fingerprint());
    }

    public function testAnonymousContextOnlyMatchesAnonymousValues()
    {
        $context = new UploadContext();

        self::assertTrue($context->matches(null, null, null));
        self::assertFalse($context->matches('', null, null));
    }
}
