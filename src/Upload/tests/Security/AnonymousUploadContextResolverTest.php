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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Upload\Security\AnonymousUploadContextResolver;

#[CoversClass(AnonymousUploadContextResolver::class)]
final class AnonymousUploadContextResolverTest extends TestCase
{
    public function testResolvesAContextWithoutOwnerTenantOrField(): void
    {
        $context = new AnonymousUploadContextResolver()->resolve();

        self::assertNull($context->ownerId);
        self::assertNull($context->tenantId);
        self::assertNull($context->fieldName);
    }
}
