<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Storage;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Storage\ImageWriteSession;
use Symfony\UX\Image\Storage\StoragePath;
use Symfony\UX\Image\Storage\StreamStorageInterface;

#[CoversClass(ImageWriteSession::class)]
final class ImageWriteSessionTest extends TestCase
{
    public function testRejectsMissingStagedFile()
    {
        $this->expectException(\InvalidArgumentException::class);

        new ImageWriteSession($this->createStub(StreamStorageInterface::class), 'media')
            ->stage(new StoragePath('missing.jpeg'), '/missing/image.jpeg');
    }

    public function testRollsBackAlreadyPublishedObjectsWhenCommitFails()
    {
        $first = tempnam(sys_get_temp_dir(), 'ux-image-write-');
        $second = tempnam(sys_get_temp_dir(), 'ux-image-write-');
        file_put_contents($first, 'first');
        file_put_contents($second, 'second');
        $storage = $this->createMock(StreamStorageInterface::class);
        $storage->expects(self::exactly(2))->method('writeStream')
            ->willReturnCallback(static function (string $storageName, StoragePath $path): void {
                if ('second.jpeg' === $path->value) {
                    throw new \RuntimeException('write failed');
                }
            });
        $storage->expects(self::once())->method('deletePath')->with('media', self::callback(static fn (StoragePath $path): bool => 'first.jpeg' === $path->value));
        $session = new ImageWriteSession($storage, 'media');
        $session->stage(new StoragePath('first.jpeg'), $first);
        $session->stage(new StoragePath('second.jpeg'), $second);

        $this->expectException(\RuntimeException::class);
        try {
            $session->commit();
        } finally {
            @unlink($first);
            @unlink($second);
        }
    }

    public function testRollbackAttemptsEveryDeletionBeforeReportingFailures()
    {
        $files = [
            'first.jpeg' => tempnam(sys_get_temp_dir(), 'ux-image-write-'),
            'second.jpeg' => tempnam(sys_get_temp_dir(), 'ux-image-write-'),
            'third.jpeg' => tempnam(sys_get_temp_dir(), 'ux-image-write-'),
        ];
        $deleted = [];
        $storage = $this->createStub(StreamStorageInterface::class);
        $storage->method('deletePath')->willReturnCallback(static function (string $storageName, StoragePath $path) use (&$deleted): void {
            $deleted[] = $path->value;
            if ('third.jpeg' === $path->value || 'first.jpeg' === $path->value) {
                throw new \RuntimeException('delete failed');
            }
        });
        $session = new ImageWriteSession($storage, 'media');

        try {
            foreach ($files as $path => $file) {
                file_put_contents($file, $path);
                $session->stage(new StoragePath($path), $file);
            }
            $session->commit();

            $session->rollback();
            self::fail('Rollback failures must be reported after every deletion has been attempted.');
        } catch (\Symfony\UX\Image\Exception\StorageException $e) {
            self::assertStringContainsString('2 image path(s)', $e->getMessage());
            self::assertSame(['third.jpeg', 'second.jpeg', 'first.jpeg'], $deleted);
        } finally {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }

    public function testCommitPreservesWriteFailureWhenRollbackAlsoFails()
    {
        $first = tempnam(sys_get_temp_dir(), 'ux-image-write-');
        $second = tempnam(sys_get_temp_dir(), 'ux-image-write-');
        file_put_contents($first, 'first');
        file_put_contents($second, 'second');
        $writeFailure = new \RuntimeException('write failed');
        $storage = $this->createStub(StreamStorageInterface::class);
        $storage->method('writeStream')->willReturnCallback(static function (string $storageName, StoragePath $path) use ($writeFailure): void {
            if ('second.jpeg' === $path->value) {
                throw $writeFailure;
            }
        });
        $storage->method('deletePath')->willThrowException(new \RuntimeException('delete failed'));
        $session = new ImageWriteSession($storage, 'media');
        $session->stage(new StoragePath('first.jpeg'), $first);
        $session->stage(new StoragePath('second.jpeg'), $second);

        try {
            $session->commit();
            self::fail('The write failure must be preserved.');
        } catch (\RuntimeException $e) {
            self::assertSame($writeFailure, $e);
        } finally {
            @unlink($first);
            @unlink($second);
        }
    }
}
