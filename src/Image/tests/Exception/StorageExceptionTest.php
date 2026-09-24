<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Exception\ExceptionInterface;
use Symfony\UX\Image\Exception\StorageException;

#[CoversClass(StorageException::class)]
final class StorageExceptionTest extends TestCase
{
    #[Test]
    public function itExtendsExceptionInterface(): void
    {
        $exception = new StorageException('test');

        self::assertInstanceOf(ExceptionInterface::class, $exception);
        self::assertInstanceOf(\Exception::class, $exception);
    }

    #[Test]
    public function storageNotFound(): void
    {
        $exception = StorageException::storageNotFound('s3_bucket');

        self::assertSame(
            'Storage "s3_bucket" not found. Check your ux_image configuration.',
            $exception->getMessage(),
        );
    }

    #[Test]
    public function uploadFailedWithoutReason(): void
    {
        $exception = StorageException::uploadFailed('photo.jpg');

        self::assertSame('Failed to upload file "photo.jpg"', $exception->getMessage());
    }

    #[Test]
    public function uploadFailedWithReason(): void
    {
        $exception = StorageException::uploadFailed('photo.jpg', 'disk full');

        self::assertSame('Failed to upload file "photo.jpg": disk full', $exception->getMessage());
    }

    #[Test]
    public function uploadFailedWithEmptyReason(): void
    {
        $exception = StorageException::uploadFailed('photo.jpg', '');

        self::assertSame('Failed to upload file "photo.jpg"', $exception->getMessage());
    }

    #[Test]
    public function deletionFailedWithoutReason(): void
    {
        $exception = StorageException::deletionFailed('/uploads/old.png');

        self::assertSame('Failed to delete file "/uploads/old.png"', $exception->getMessage());
    }

    #[Test]
    public function deletionFailedWithReason(): void
    {
        $exception = StorageException::deletionFailed('/uploads/old.png', 'permission denied');

        self::assertSame(
            'Failed to delete file "/uploads/old.png": permission denied',
            $exception->getMessage(),
        );
    }

    #[Test]
    public function deletionFailedWithEmptyReason(): void
    {
        $exception = StorageException::deletionFailed('/uploads/old.png', '');

        self::assertSame('Failed to delete file "/uploads/old.png"', $exception->getMessage());
    }

    #[Test]
    public function readFailedIncludesReason(): void
    {
        self::assertSame(
            'Failed to read file "photo.jpg": unavailable',
            StorageException::readFailed('photo.jpg', 'unavailable')->getMessage(),
        );
    }

    #[Test]
    public function writeFailedIncludesReason(): void
    {
        self::assertSame(
            'Failed to write file "photo.jpg": read only',
            StorageException::writeFailed('photo.jpg', 'read only')->getMessage(),
        );
    }
}
