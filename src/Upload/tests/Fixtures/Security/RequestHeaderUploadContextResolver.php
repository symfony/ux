<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Fixtures\Security;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\Upload\Security\UploadContext;
use Symfony\UX\Upload\Security\UploadContextResolverInterface;

/**
 * Stands in for authentication in functional tests: the owner comes from the "X-Test-Owner" header.
 */
final class RequestHeaderUploadContextResolver implements UploadContextResolverInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function resolve(): UploadContext
    {
        $owner = $this->requestStack->getMainRequest()?->headers->get('X-Test-Owner');

        return new UploadContext('' === $owner ? null : $owner);
    }
}
