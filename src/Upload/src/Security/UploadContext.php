<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Security;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final readonly class UploadContext
{
    public function __construct(
        public ?string $ownerId = null,
        public ?string $tenantId = null,
        public ?string $fieldName = null,
    ) {
    }

    public function getOwnerId(): ?string
    {
        return $this->ownerId;
    }

    public function getTenantId(): ?string
    {
        return $this->tenantId;
    }

    public function getFieldName(): ?string
    {
        return $this->fieldName;
    }

    public function matches(?string $ownerId, ?string $tenantId, ?string $fieldName): bool
    {
        return $this->equals($this->ownerId, $ownerId)
            && $this->equals($this->tenantId, $tenantId)
            && $this->equals($this->fieldName, $fieldName);
    }

    public function fingerprint(): string
    {
        return hash('sha256', implode("\0", [
            $this->ownerId ?? '',
            $this->tenantId ?? '',
            $this->fieldName ?? '',
        ]));
    }

    private function equals(?string $expected, ?string $actual): bool
    {
        return null === $expected ? null === $actual : hash_equals($expected, $actual ?? '');
    }
}
