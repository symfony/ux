<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Test;

use Symfony\UX\Upload\Security\UploadContext;
use Symfony\UX\Upload\Security\UploadContextResolverInterface;

/**
 * Resolves one fixed context for deterministic ownership and token tests.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class TestUploadContextResolver implements UploadContextResolverInterface
{
    public const string DEFAULT_OWNER_ID = 'user-1';

    public function __construct(
        private readonly ?string $ownerId = self::DEFAULT_OWNER_ID,
        private readonly ?string $tenantId = null,
        private readonly ?string $fieldName = null,
    ) {
    }

    public function resolve(): UploadContext
    {
        return new UploadContext($this->ownerId, $this->tenantId, $this->fieldName);
    }
}
