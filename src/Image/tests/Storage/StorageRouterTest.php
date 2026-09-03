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

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\Image\Exception\ExceptionInterface;
use Symfony\UX\Image\Exception\RuntimeException;
use Symfony\UX\Image\ImageAsset;
use Symfony\UX\Image\Processor\GdImageProcessor;
use Symfony\UX\Image\Processor\ImageInspector;
use Symfony\UX\Image\Renderer\DefaultImageRenderer;
use Symfony\UX\Image\Storage\FlysystemStorage;
use Symfony\UX\Image\Storage\LocalStorage;
use Symfony\UX\Image\Storage\StorageInterface;
use Symfony\UX\Image\Storage\StoragePath;
use Symfony\UX\Image\Storage\StorageRouter;
use Symfony\UX\Image\Storage\StreamStorageInterface;
use Symfony\UX\Image\UrlGenerator\GenericUrlAdapter;
use Symfony\UX\Image\UrlGenerator\UrlGenerator;

/**
 * @requires interface League\Flysystem\FilesystemOperator
 */
#[CoversClass(StorageRouter::class)]
final class StorageRouterTest extends TestCase
{
    private string $localRoot;
    private string $flysystemRoot;

    protected function setUp(): void
    {
        $base = sys_get_temp_dir().'/ux-image-router-'.uniqid('', true);
        $this->localRoot = $base.'/local';
        $this->flysystemRoot = $base.'/flysystem';
        new SymfonyFilesystem()->mkdir([$this->localRoot, $this->flysystemRoot]);
    }

    protected function tearDown(): void
    {
        new SymfonyFilesystem()->remove(\dirname($this->localRoot));
    }

    public function testStoreRoutesConfiguredNameThroughFlysystemNotLocal()
    {
        $flysystem = $this->flysystemOperator();
        $router = new StorageRouter(
            new ServiceLocator(['media' => static fn (): StorageInterface => new FlysystemStorage($flysystem)]),
            $this->localStorage(),
        );

        $path = $router->store($this->uploadedFile(), 'media');

        self::assertStringStartsWith('/', $path);
        // The write went through Flysystem, so the file exists there and not on local disk.
        self::assertTrue($router->exists(new ImageAsset('media', $path)));
        self::assertTrue($flysystem->fileExists(ltrim($path, '/')));
        self::assertDirectoryDoesNotExist($this->localRoot.'/media');
    }

    public function testRoutesConfiguredNameThroughCustomAdapterService()
    {
        $adapter = new RecordingStorage();
        $router = new StorageRouter(
            new ServiceLocator(['custom' => static fn (): StorageInterface => $adapter]),
            $this->localStorage(),
        );

        $asset = new ImageAsset('custom', '/custom/photo.jpg');
        $file = new UploadedFile($this->stubSource(), 'photo.jpg', 'image/jpeg', null, true);

        $router->store($file, 'custom');
        $router->delete($asset);
        $router->exists($asset);
        $router->getPublicUrl($asset);
        $router->getFilePath($asset);

        self::assertSame(['store', 'delete', 'exists', 'getPublicUrl', 'getFilePath'], $adapter->calls);
    }

    public function testRejectsUnconfiguredStorageName()
    {
        $router = new StorageRouter(new ServiceLocator([]), $this->localStorage());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown image storage "default"');
        $router->store($this->uploadedFile(), 'default');
    }

    public function testDeclaredLocalOnlyNameFallsBackToLocalStorageHonoringPublicUrlPrefix()
    {
        // "assets" is a declared, local-only storage: it has no entry in the backend
        // locator, so the router falls back to LocalStorage, which applies the
        // storage's configured public_url_prefix.
        $local = new LocalStorage(
            ['assets' => ['public_url_prefix' => 'https://cdn.example.com']],
            $this->localRoot,
        );
        $router = new StorageRouter(
            new ServiceLocator(['other' => static fn (): StorageInterface => new RecordingStorage()]),
            $local,
        );

        $url = $router->getPublicUrl(new ImageAsset('assets', '/assets/photo.jpg'));

        self::assertSame('https://cdn.example.com/assets/photo.jpg', $url);
    }

    public function testNonStreamBackendFailureImplementsPackageExceptionMarker()
    {
        $router = new StorageRouter(
            new ServiceLocator(['custom' => static fn (): StorageInterface => new RecordingStorage()]),
            $this->localStorage(),
        );

        try {
            $router->readStream('custom', StoragePath::fromAssetPath('/custom/photo.jpg'));
            self::fail('A non-stream backend must be rejected.');
        } catch (ExceptionInterface $e) {
            self::assertInstanceOf(RuntimeException::class, $e);
            self::assertStringContainsString('must implement', $e->getMessage());
        }
    }

    public function testDelegatesEveryStreamOperation()
    {
        $stream = fopen('php://temp', 'w+');
        $backend = $this->createMock(StreamStorageInterface::class);
        $path = new StoragePath('photo.jpg');
        $backend->expects(self::once())->method('readStream')->with('media', $path)->willReturn($stream);
        $backend->expects(self::once())->method('writeStream')->with('media', $path, $stream);
        $backend->expects(self::once())->method('deletePath')->with('media', $path);
        $router = new StorageRouter(
            new ServiceLocator(['media' => static fn (): StorageInterface => $backend]),
            $this->localStorage(),
        );

        self::assertSame($stream, $router->readStream('media', $path));
        $router->writeStream('media', $path, $stream);
        $router->deletePath('media', $path);
        fclose($stream);
    }

    #[RequiresPhpExtension('gd')]
    public function testGdProcessingUsesFlysystemStreamsEndToEnd()
    {
        $flysystem = $this->flysystemOperator();
        $router = new StorageRouter(
            new ServiceLocator(['media' => static fn (): StorageInterface => new FlysystemStorage($flysystem)]),
            $this->localStorage(),
        );
        $source = $this->localRoot.'/real.png';
        $image = imagecreatetruecolor(80, 60);
        imagepng($image, $source);
        $file = new UploadedFile($source, 'real.png', 'image/png', null, true);
        $processor = new GdImageProcessor($router, [
            'thumbnail' => [
                'formats' => ['jpeg'],
                'variants' => ['small' => ['width' => 40, 'media' => '(max-width: 640px)']],
            ],
        ], new ImageInspector());

        $asset = $processor->process($file, 'thumbnail', 'media');

        self::assertSame('media', $asset->storageName);
        self::assertNotEmpty($asset->variants['jpeg']);
        $variantPath = ltrim($asset->variants['jpeg'][0]['path'], '/');
        self::assertTrue($flysystem->fileExists($variantPath));
        self::assertMatchesRegularExpression('/_[a-f0-9]{24}_small\\.jpeg$/', $variantPath);

        $renderer = new DefaultImageRenderer(new UrlGenerator(
            [],
            [new GenericUrlAdapter()],
            ['media' => ['public_url_prefix' => '/media']],
        ));
        $rendered = $renderer->render($asset);
        self::assertSame('(max-width: 640px)', $rendered->sources[0]['media']);
        self::assertStringContainsString('/media/', $rendered->sources[0]['srcset']);
    }

    private function flysystemOperator(): FilesystemOperator
    {
        return new Filesystem(new LocalFilesystemAdapter($this->flysystemRoot));
    }

    private function localStorage(): LocalStorage
    {
        return new LocalStorage([], $this->localRoot);
    }

    private function uploadedFile(): UploadedFile
    {
        return new UploadedFile($this->stubSource(), 'photo.jpg', 'image/jpeg', null, true);
    }

    private function stubSource(): string
    {
        $tmp = $this->localRoot.'/source_'.uniqid('', true).'.jpg';
        file_put_contents($tmp, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true));

        return $tmp;
    }
}

/**
 * @internal
 */
final class RecordingStorage implements StorageInterface
{
    /** @var list<string> */
    public array $calls = [];

    public function store(UploadedFile $file, string $storageName, ?string $directory = null): string
    {
        $this->calls[] = 'store';

        return '/'.$storageName.'/stored.jpg';
    }

    public function delete(ImageAsset $imageAsset): bool
    {
        $this->calls[] = 'delete';

        return true;
    }

    public function exists(ImageAsset $imageAsset): bool
    {
        $this->calls[] = 'exists';

        return true;
    }

    public function getPublicUrl(ImageAsset $imageAsset, ?string $variant = null): string
    {
        $this->calls[] = 'getPublicUrl';

        return $imageAsset->path;
    }

    public function getFilePath(ImageAsset $imageAsset): string
    {
        $this->calls[] = 'getFilePath';

        return $imageAsset->path;
    }
}
