<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Test;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\UX\Upload\Security\UploadContextResolverInterface;
use Symfony\UX\Upload\Storage\InMemoryStorage;
use Symfony\UX\Upload\Test\CompletedUploadFactory;
use Symfony\UX\Upload\Test\TestUploadContextResolver;
use Symfony\UX\Upload\Token\UploadTokenHandler;

#[CoversClass(TestUploadContextResolver::class)]
final class TestUploadContextResolverTest extends TestCase
{
    public function testItImplementsTheProductionContractWithDeterministicDefaults()
    {
        $resolver = new TestUploadContextResolver();

        self::assertInstanceOf(UploadContextResolverInterface::class, $resolver);
        self::assertSame(TestUploadContextResolver::DEFAULT_OWNER_ID, $resolver->resolve()->ownerId);
        self::assertNull($resolver->resolve()->tenantId);
        self::assertNull($resolver->resolve()->fieldName);
    }

    public function testEveryContextValueCanBeOverriddenIncludingAnonymousOwnership()
    {
        $context = new TestUploadContextResolver(
            ownerId: null,
            tenantId: 'tenant-7',
            fieldName: 'profile.avatar',
        )->resolve();

        self::assertNull($context->ownerId);
        self::assertSame('tenant-7', $context->tenantId);
        self::assertSame('profile.avatar', $context->fieldName);
    }

    public function testItDrivesRealOwnerBoundFormTokens()
    {
        $storage = new InMemoryStorage();
        $upload = new CompletedUploadFactory(ownerId: 'user-42')->create($storage);
        $signer = new UriSigner('secret');
        $ownerHandler = new UploadTokenHandler(
            $signer,
            $storage,
            contextResolver: new TestUploadContextResolver(ownerId: 'user-42'),
        );
        $otherOwnerHandler = new UploadTokenHandler(
            $signer,
            $storage,
            contextResolver: new TestUploadContextResolver(ownerId: 'user-99'),
        );

        $token = $ownerHandler->generate($upload);

        self::assertEquals($upload, $ownerHandler->resolve($token));
        self::assertNull($otherOwnerHandler->resolve($token));
    }
}
