<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Processor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Exception\ImageLimitExceededException;
use Symfony\UX\Image\ImageAsset;
use Symfony\UX\Image\ProcessingLimits;
use Symfony\UX\Image\Processor\ProcessingWorkspace;
use Symfony\UX\Image\Storage\StoragePath;
use Symfony\UX\Image\Storage\StreamStorageInterface;

#[CoversClass(ProcessingWorkspace::class)]
final class ProcessingWorkspaceTest extends TestCase
{
    public function testMaterializesStreamOnceAndCleansWorkspace()
    {
        $stream = fopen('php://temp', 'w+');
        $imageData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
        fwrite($stream, $imageData);
        rewind($stream);
        $storage = $this->createMock(StreamStorageInterface::class);
        $storage->expects(self::once())->method('readStream')->with('media', self::isInstanceOf(StoragePath::class))->willReturn($stream);
        $workspace = new ProcessingWorkspace();

        $local = $workspace->materialize($storage, new ImageAsset('media', '/media/original.jpeg'));
        self::assertSame($imageData, file_get_contents($local));
        $directory = \dirname($local);

        $workspace->cleanup();
        self::assertDirectoryDoesNotExist($directory);
    }

    public function testRejectsOversizedStreamBeforeImageDecoding()
    {
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, str_repeat('x', 11));
        rewind($stream);
        $storage = $this->createStub(StreamStorageInterface::class);
        $storage->method('readStream')->willReturn($stream);
        $workspace = new ProcessingWorkspace();

        $this->expectException(ImageLimitExceededException::class);
        $this->expectExceptionMessage('11 bytes');

        $workspace->materialize(
            $storage,
            new ImageAsset('media', '/media/original.jpeg'),
            new ProcessingLimits(maxInputBytes: 10),
        );
    }

    public function testMaterializesLocalImageAndBuildsWorkspacePath()
    {
        $source = tempnam(sys_get_temp_dir(), 'ux_image_source_');
        file_put_contents($source, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true));
        $workspace = new ProcessingWorkspace();

        try {
            $local = $workspace->materializeLocal($source);
            self::assertFileExists($local);
            self::assertStringContainsString('variant.webp', $workspace->path('variant.webp'));
        } finally {
            $workspace->cleanup();
            @unlink($source);
        }
    }

    public function testMaterializeLocalRejectsMissingFile()
    {
        $workspace = new ProcessingWorkspace();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not read local image');

        $workspace->materializeLocal('/missing/image.jpg');
    }
}
