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

use PHPUnit\Framework\TestCase;
use Symfony\UX\Upload\Storage\InMemoryStorage;
use Symfony\UX\Upload\Test\CompletedUploadFactory;

final class CompletedUploadFactoryTest extends TestCase
{
    public function testCreatesAReadableDeterministicUploadWithSecurityContext(): void
    {
        $storage = new InMemoryStorage();
        $upload = new CompletedUploadFactory(
            id: str_repeat('a', 32),
            uploader: 'documents',
            originalName: 'report.csv',
            mimeType: 'text/csv',
            size: 7,
            ownerId: 'user-1',
            tenantId: 'tenant-1',
            fieldName: 'document.file',
        )->create($storage, 'a,b,c,d');

        self::assertSame(str_repeat('a', 32), $upload->getId());
        self::assertSame('documents', $upload->getUploaderName());
        self::assertSame('report.csv', $upload->getOriginalName());
        self::assertSame('text/csv', $upload->getMimeType());
        self::assertSame(7, $upload->getSize());
        self::assertSame('user-1', $upload->getOwnerId());
        self::assertSame('tenant-1', $upload->getTenantId());
        self::assertSame('document.file', $upload->getFieldName());
        self::assertStringEndsWith('-'.str_repeat('a', 32).'.csv', $upload->getTemporaryPath());

        $stream = $upload->openStream();
        try {
            self::assertSame('a,b,c,d', stream_get_contents($stream));
        } finally {
            fclose($stream);
        }
    }

    public function testCreatesAnExtensionlessUploadWithInternalStorage(): void
    {
        $upload = new CompletedUploadFactory(originalName: 'LICENSE')->create(content: 'text');

        self::assertStringEndsWith('-0123456789abcdef0123456789abcdef', $upload->getTemporaryPath());
        self::assertFalse($upload->isExpired(new \DateTimeImmutable('2030-01-01T00:00:00+00:00')));
    }
}
