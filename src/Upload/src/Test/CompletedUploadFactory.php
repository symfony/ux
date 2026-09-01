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

use Symfony\UX\Upload\Storage\InMemoryStorage;
use Symfony\UX\Upload\Storage\StorageInterface;
use Symfony\UX\Upload\Upload\CompletedUpload;
use Symfony\UX\Upload\Upload\CompletedUploadAccess;

/**
 * Creates deterministic completed uploads for application tests.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class CompletedUploadFactory
{
    public function __construct(
        private readonly string $id = '0123456789abcdef0123456789abcdef',
        private readonly string $uploader = 'default',
        private readonly string $originalName = 'document.txt',
        private readonly string $mimeType = 'text/plain',
        private readonly int $size = 4,
        private readonly ?string $ownerId = null,
        private readonly ?string $tenantId = null,
        private readonly ?string $fieldName = null,
    ) {
    }

    public function create(?StorageInterface $storage = null, string $content = 'data'): CompletedUpload
    {
        $storage ??= new InMemoryStorage();
        $createdAt = new \DateTimeImmutable('2026-01-01T12:00:00+00:00');
        $expiresAt = new \DateTimeImmutable('2036-01-01T12:00:00+00:00');
        $extension = pathinfo($this->originalName, \PATHINFO_EXTENSION);
        $path = \sprintf('.tmp/completed/%d-%s%s', $expiresAt->getTimestamp(), $this->id, '' !== $extension ? '.'.$extension : '');
        $storage->write($path, $content);

        return new CompletedUpload(
            id: $this->id,
            uploader: $this->uploader,
            path: $path,
            originalName: $this->originalName,
            mimeType: $this->mimeType,
            size: $this->size,
            createdAt: $createdAt,
            expiresAt: $expiresAt,
            ownerId: $this->ownerId,
            tenantId: $this->tenantId,
            fieldName: $this->fieldName,
            access: new CompletedUploadAccess($storage),
        );
    }
}
