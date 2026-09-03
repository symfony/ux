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
}
