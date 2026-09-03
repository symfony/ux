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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\Image\Exception\StorageException;
use Symfony\UX\Image\ImageAsset;
use Symfony\UX\Image\Storage\LocalStorage;
use Symfony\UX\Image\Storage\StoragePath;

#[CoversClass(LocalStorage::class)]
final class LocalStorageTest extends TestCase
{
    private string $storageRoot;

    protected function setUp(): void
    {
        $this->storageRoot = sys_get_temp_dir().'/ux_image_local_storage_test_'.uniqid();
        mkdir($this->storageRoot, 0o777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->storageRoot);
    }

    public function testStore()
    {
        $storage = new LocalStorage([], $this->storageRoot);

        $tmpFile = $this->storageRoot.'/source_test.jpg';
        $this->writePng($tmpFile);

        $file = new UploadedFile($tmpFile, 'photo.jpg', 'image/jpeg', null, true);

        $path = $storage->store($file, 'default_public');

        self::assertStringStartsWith('/', $path);
        self::assertStringEndsWith('.png', $path);
    }

    public function testStoreWithDirectory()
    {
        $storage = new LocalStorage(['media' => []], $this->storageRoot);

        $tmpFile = $this->storageRoot.'/source_dir.jpg';
        $this->writePng($tmpFile);

        $file = new UploadedFile($tmpFile, 'photo.jpg', 'image/jpeg', null, true);

        $path = $storage->store($file, 'media', 'avatars');

        self::assertStringStartsWith('/avatars/', $path);
        self::assertStringContainsString('avatars/', $path);
    }

    public function testDelete()
    {
        $storage = new LocalStorage([], $this->storageRoot);

        $tmpFile = $this->storageRoot.'/source_del.jpg';
        $this->writePng($tmpFile);
        $file = new UploadedFile($tmpFile, 'photo.jpg', 'image/jpeg', null, true);

        $path = $storage->store($file, 'default_public');
        $asset = new ImageAsset('default_public', $path);

        self::assertTrue($storage->exists($asset));
        self::assertTrue($storage->delete($asset));
        self::assertFalse($storage->exists($asset));
    }

    public function testDeleteNonExistentFile()
    {
        $storage = new LocalStorage([], $this->storageRoot);
        $asset = new ImageAsset('default_public', '/nonexistent.jpg');

        self::assertFalse($storage->delete($asset));
    }

    public function testDeleteRemovesOriginalAndVariants()
    {
        $storage = new LocalStorage([], $this->storageRoot);
        mkdir($this->storageRoot.'/default_public', 0o777, true);
        file_put_contents($this->storageRoot.'/default_public/original.jpeg', 'original');
        file_put_contents($this->storageRoot.'/default_public/thumb.webp', 'variant');
        $asset = new ImageAsset('default_public', '/original.jpeg', variants: [
            'webp' => [['path' => '/thumb.webp', 'width' => 100]],
        ]);

        self::assertTrue($storage->delete($asset));
        self::assertFileDoesNotExist($this->storageRoot.'/default_public/original.jpeg');
        self::assertFileDoesNotExist($this->storageRoot.'/default_public/thumb.webp');
        self::assertFalse($storage->delete($asset));
    }

    public function testExists()
    {
        $storage = new LocalStorage([], $this->storageRoot);

        $asset = new ImageAsset('default_public', '/nonexistent.jpg');
        self::assertFalse($storage->exists($asset));

        // Create the file manually
        $dir = $this->storageRoot.'/default_public';
        mkdir($dir, 0o777, true);
        file_put_contents($dir.'/existing.jpg', 'data');

        $existingAsset = new ImageAsset('default_public', '/existing.jpg');
        self::assertTrue($storage->exists($existingAsset));
    }

    public function testGetPublicUrlWithPrefix()
    {
        $storages = [
            'cdn_storage' => [
                'public_url_prefix' => '/uploads',
            ],
        ];

        $storage = new LocalStorage($storages, $this->storageRoot);
        $asset = new ImageAsset('cdn_storage', '/images/photo.jpg');

        self::assertSame('/uploads/images/photo.jpg', $storage->getPublicUrl($asset));
    }

    public function testGetPublicUrlWithoutPrefix()
    {
        $storage = new LocalStorage([], $this->storageRoot);
        $asset = new ImageAsset('default_public', '/images/photo.jpg');

        self::assertSame('/images/photo.jpg', $storage->getPublicUrl($asset));
    }

    public function testGetPublicUrlWithVariant()
    {
        $storages = [
            'default_public' => [
                'public_url_prefix' => '/uploads',
            ],
        ];

        $storage = new LocalStorage($storages, $this->storageRoot);
        $asset = new ImageAsset('default_public', '/photo.jpg');

        self::assertSame('/uploads/thumb.jpg', $storage->getPublicUrl($asset, '/thumb.jpg'));
    }

    public function testGetFilePath()
    {
        $storage = new LocalStorage([], $this->storageRoot);
        $asset = new ImageAsset('default_public', '/images/photo.jpg');

        self::assertSame($this->storageRoot.'/default_public/images/photo.jpg', $storage->getFilePath($asset));
    }

    public function testRejectsSymlinkEscapeWhenFinalParentDoesNotExist()
    {
        $storage = new LocalStorage([], $this->storageRoot);
        $storageDirectory = $this->storageRoot.'/default_public';
        $outside = $this->storageRoot.'_outside';
        mkdir($storageDirectory, 0o777, true);
        mkdir($outside, 0o777, true);
        symlink($outside, $storageDirectory.'/linked');

        try {
            $this->expectException(StorageException::class);
            $this->expectExceptionMessage('escapes');
            $storage->getFilePath(new ImageAsset('default_public', '/linked/missing/photo.jpg'));
        } finally {
            new Filesystem()->remove($storageDirectory.'/linked');
            rmdir($outside);
        }
    }

    public function testStoreRejectsSymlinkEscape()
    {
        $storage = new LocalStorage([], $this->storageRoot);
        $storageDirectory = $this->storageRoot.'/default_public';
        $outside = $this->storageRoot.'_outside';
        mkdir($storageDirectory, 0o777, true);
        mkdir($outside, 0o777, true);
        symlink($outside, $storageDirectory.'/linked');
        $source = $this->storageRoot.'/source.png';
        $this->writePng($source);

        try {
            $this->expectException(StorageException::class);
            $storage->store(new UploadedFile($source, 'photo.png', 'image/png', null, true), 'default_public', 'linked/missing');
        } finally {
            new Filesystem()->remove($storageDirectory.'/linked');
            rmdir($outside);
        }
    }

    public function testClientFilenameCannotChooseStoredExtension()
    {
        $storage = new LocalStorage([], $this->storageRoot);
        $tmpFile = $this->storageRoot.'/source';
        $this->writePng($tmpFile);

        $path = $storage->store(new UploadedFile($tmpFile, 'payload.php', 'application/x-php', null, true), 'default_public');

        self::assertStringEndsWith('.png', $path);
        self::assertStringNotContainsString('payload', $path);
        self::assertStringNotContainsString('.php', $path);
    }

    public function testStreamWriteNeverOverwritesImmutableObject()
    {
        $storage = new LocalStorage([], $this->storageRoot);
        mkdir($this->storageRoot.'/default_public', 0o777, true);
        file_put_contents($this->storageRoot.'/default_public/generation.jpeg', 'old');
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, 'new');
        rewind($stream);

        $this->expectException(\RuntimeException::class);
        try {
            $storage->writeStream('default_public', new StoragePath('generation.jpeg'), $stream);
        } finally {
            fclose($stream);
            self::assertSame('old', file_get_contents($this->storageRoot.'/default_public/generation.jpeg'));
        }
    }

    public function testStreamRoundTripAndDeletion()
    {
        $storage = new LocalStorage([], $this->storageRoot);
        $input = fopen('php://temp', 'w+');
        fwrite($input, 'image bytes');
        rewind($input);
        $path = new StoragePath('generated/image.bin');

        $storage->writeStream('default_public', $path, $input);
        fclose($input);

        $output = $storage->readStream('default_public', $path);
        self::assertSame('image bytes', stream_get_contents($output));
        fclose($output);

        $storage->deletePath('default_public', $path);
        self::assertFileDoesNotExist($this->storageRoot.'/default_public/generated/image.bin');
    }

    public function testReadStreamRejectsMissingObject()
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Failed to read');

        new LocalStorage([], $this->storageRoot)->readStream('default_public', new StoragePath('missing.jpg'));
    }

    public function testWriteStreamRejectsInvalidResource()
    {
        $this->expectException(\InvalidArgumentException::class);

        new LocalStorage([], $this->storageRoot)->writeStream('default_public', new StoragePath('image.jpg'), false);
    }

    public function testRejectsUnknownStorage()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown image storage');

        new LocalStorage([], $this->storageRoot)->getFilePath(new ImageAsset('missing', '/image.jpg'));
    }

    public function testRejectsNonImageBeforeWriting()
    {
        $storage = new LocalStorage([], $this->storageRoot);
        $tmpFile = $this->storageRoot.'/payload';
        file_put_contents($tmpFile, '<?php echo "unsafe";');

        $this->expectException(\Symfony\UX\Image\Exception\ImageProcessingException::class);
        try {
            $storage->store(new UploadedFile($tmpFile, 'avatar.jpg', 'image/jpeg', null, true), 'default_public');
        } finally {
            self::assertDirectoryDoesNotExist($this->storageRoot.'/default_public');
        }
    }

    #[DataProvider('unsafePaths')]
    public function testRejectsUnsafeAssetPaths(string $path)
    {
        $storage = new LocalStorage([], $this->storageRoot);

        $this->expectException(\InvalidArgumentException::class);
        $storage->exists(new ImageAsset('default_public', $path));
    }

    public static function unsafePaths(): iterable
    {
        yield ['../secret.jpg'];
        yield ['/default/foo/../../secret.jpg'];
        yield ['foo\\bar.jpg'];
        yield ["foo\0bar.jpg"];
    }

    private function writePng(string $path): void
    {
        file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true));
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getRealPath());
            } else {
                unlink($item->getRealPath());
            }
        }

        rmdir($dir);
    }
}
