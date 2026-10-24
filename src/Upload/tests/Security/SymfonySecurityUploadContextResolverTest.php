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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\UX\Upload\Security\SymfonySecurityUploadContextResolver;

final class SymfonySecurityUploadContextResolverTest extends TestCase
{
    public function testUsesAuthenticatedUserIdentifierAsOwner(): void
    {
        $user = new class implements UserInterface {
            public function getRoles(): array
            {
                return ['ROLE_USER'];
            }

            public function eraseCredentials(): void
            {
            }

            public function getUserIdentifier(): string
            {
                return 'user@example.com';
            }
        };
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);

        $context = new SymfonySecurityUploadContextResolver($security)->resolve();

        self::assertSame('user@example.com', $context->ownerId);
    }

    public function testFallsBackToAnonymousContextWithoutSecurityService(): void
    {
        self::assertNull(new SymfonySecurityUploadContextResolver(null)->resolve()->ownerId);
    }
}
