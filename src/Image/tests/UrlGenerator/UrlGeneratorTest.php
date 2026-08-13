<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\UrlGenerator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\ImageAsset;
use Symfony\UX\Image\Storage\StorageInterface;
use Symfony\UX\Image\UrlGenerator\GenericUrlAdapter;
use Symfony\UX\Image\UrlGenerator\StorageUrlAdapter;
use Symfony\UX\Image\UrlGenerator\UrlAdapterInterface;
use Symfony\UX\Image\UrlGenerator\UrlGenerator;

#[CoversClass(UrlGenerator::class)]
#[CoversClass(GenericUrlAdapter::class)]
#[CoversClass(StorageUrlAdapter::class)]
final class UrlGeneratorTest extends TestCase
{
    public function testUsesCustomAdapterWhenProvided(): void
    {
        $asset = new ImageAsset('custom', '/path/to/image.jpg');

        $adapter = new class implements UrlAdapterInterface {
            public function resolve(string $path, array $storageConfig, array $variantConfig = [], ?string $storageName = null): string
            {
                return '/adapted/'.ltrim($path, '/');
            }

            public static function getName(): string
            {
                return 'custom_adapter';
            }
        };

        $generator = new UrlGenerator(
            [],
            [$adapter],
            [
                'custom' => [
                    'url_adapter' => 'custom_adapter',
                ],
            ],
        );

        self::assertSame('/adapted/path/to/image.jpg', $generator->generateAssetUrl($asset));
    }

    public function testFallsBackToGenericAdapter(): void
    {
        $asset = new ImageAsset('generic', '/relative/path.png');

        $generator = new UrlGenerator(
            [],
            [new GenericUrlAdapter()],
            [
                'generic' => [
                    'public_url_prefix' => '/uploads',
                ],
            ],
        );

        self::assertSame('/uploads/relative/path.png', $generator->generateAssetUrl($asset));
    }

    public function testStorageAdapterUsesStoragePublicUrlContract(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(self::once())
            ->method('getPublicUrl')
            ->with(self::callback(static fn (ImageAsset $asset): bool => 'custom' === $asset->storageName && '/photo.jpg' === $asset->path), null)
            ->willReturn('https://storage.example/photo.jpg');
        $generator = new UrlGenerator(
            [],
            [new GenericUrlAdapter(), new StorageUrlAdapter($storage)],
            ['custom' => ['url_adapter' => 'storage']],
        );

        self::assertSame('https://storage.example/photo.jpg', $generator->generateAssetUrl(new ImageAsset('custom', '/photo.jpg')));
    }

    public function testStorageAdapterUsesVariantPathAndRequiresStorageName(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(self::once())
            ->method('getPublicUrl')
            ->with(self::isInstanceOf(ImageAsset::class), '/thumb.jpg')
            ->willReturn('/public/thumb.jpg');
        $adapter = new StorageUrlAdapter($storage);

        self::assertSame('storage', StorageUrlAdapter::getName());
        self::assertSame('/public/thumb.jpg', $adapter->resolve('/photo.jpg', [], ['path' => '/thumb.jpg'], 'media'));

        $this->expectException(\InvalidArgumentException::class);
        $adapter->resolve('/photo.jpg', [], [], '');
    }

    public function testGenericAdapterNameAndEmptyPrefix(): void
    {
        $adapter = new GenericUrlAdapter();

        self::assertSame('generic', GenericUrlAdapter::getName());
        self::assertSame('/photo.jpg', $adapter->resolve('/photo.jpg', ['public_url_prefix' => null]));
    }

    public function testGenerateVariantUrl(): void
    {
        $asset = new ImageAsset('generic', '/images/photo.jpg');

        $generator = new UrlGenerator(
            [],
            [new GenericUrlAdapter()],
            [
                'generic' => [
                    'public_url_prefix' => '/uploads',
                ],
            ],
        );

        $variant = ['path' => '/images/photo_thumb.jpg', 'width' => 150];

        self::assertSame('/uploads/images/photo_thumb.jpg', $generator->generateVariantUrl($asset, $variant));
    }

    public function testGenerateVariantUrlFallsBackToAssetPathWhenNoVariantPath(): void
    {
        $asset = new ImageAsset('generic', '/images/photo.jpg');

        $generator = new UrlGenerator(
            [],
            [new GenericUrlAdapter()],
            [
                'generic' => [
                    'public_url_prefix' => '/uploads',
                ],
            ],
        );

        $variant = ['width' => 150];

        self::assertSame('/uploads/images/photo.jpg', $generator->generateVariantUrl($asset, $variant));
    }

    public function testCdnUrlBuilderDelegation(): void
    {
        $cdnBuilder = new class implements \Symfony\UX\Image\UrlGenerator\CdnUrlBuilderInterface {
            public function buildUrl(string $baseUrl, string $path, array $profileConfig, array $variantConfig): string
            {
                return $baseUrl.'/cdn/'.ltrim($path, '/');
            }

            public static function getProviderName(): string
            {
                return 'test_cdn';
            }
        };

        $asset = new ImageAsset('my_storage', '/images/photo.jpg');

        $generator = new UrlGenerator(
            [$cdnBuilder],
            [new GenericUrlAdapter()],
            [
                'my_storage' => [
                    'cdn' => [
                        'provider' => 'test_cdn',
                        'base_url' => 'https://cdn.example.com',
                    ],
                ],
            ],
        );

        self::assertSame('https://cdn.example.com/cdn/images/photo.jpg', $generator->generateAssetUrl($asset));
    }

    public function testCdnUrlBuilderDelegationForVariant(): void
    {
        $cdnBuilder = new class implements \Symfony\UX\Image\UrlGenerator\CdnUrlBuilderInterface {
            public function buildUrl(string $baseUrl, string $path, array $profileConfig, array $variantConfig): string
            {
                return $baseUrl.'/'.ltrim($path, '/');
            }

            public static function getProviderName(): string
            {
                return 'test_cdn';
            }
        };

        $asset = new ImageAsset('my_storage', '/images/photo.jpg');

        $generator = new UrlGenerator(
            [$cdnBuilder],
            [new GenericUrlAdapter()],
            [
                'my_storage' => [
                    'cdn' => [
                        'provider' => 'test_cdn',
                        'base_url' => 'https://cdn.example.com',
                    ],
                ],
            ],
        );

        $variant = ['path' => '/images/photo_thumb.jpg', 'width' => 150];

        self::assertSame('https://cdn.example.com/images/photo_thumb.jpg', $generator->generateVariantUrl($asset, $variant));
    }

    public function testConfiguredUnknownCdnBuilderThrowsException(): void
    {
        $generator = new UrlGenerator(
            [],
            [new GenericUrlAdapter()],
            [
                'media' => [
                    'cdn' => [
                        'provider' => 'missing',
                        'base_url' => 'https://cdn.example.com',
                    ],
                ],
            ],
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown CDN URL builder "missing"');

        $generator->generateAssetUrl(new ImageAsset('media', '/photo.jpg'));
    }

    public function testAbsoluteUrlIsReturnedUnchanged(): void
    {
        $asset = new ImageAsset('default', 'https://external.com/photo.jpg');

        $generator = new UrlGenerator(
            [],
            [new GenericUrlAdapter()],
            [],
        );

        self::assertSame('https://external.com/photo.jpg', $generator->generateAssetUrl($asset));
    }

    public function testHttpAbsoluteUrlIsReturnedUnchanged(): void
    {
        $asset = new ImageAsset('default', 'http://external.com/photo.jpg');

        $generator = new UrlGenerator(
            [],
            [new GenericUrlAdapter()],
            [],
        );

        self::assertSame('http://external.com/photo.jpg', $generator->generateAssetUrl($asset));
    }

    public function testUnknownAdapterThrowsException(): void
    {
        $asset = new ImageAsset('custom', '/path/to/image.jpg');

        $generator = new UrlGenerator(
            [],
            [],
            [
                'custom' => [
                    'url_adapter' => 'nonexistent_adapter',
                ],
            ],
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown URL adapter "nonexistent_adapter". Make sure it is configured and tagged as "ux_image.storage_adapter".');

        $generator->generateAssetUrl($asset);
    }

    public function testStorageWithoutAdapterFallsBackToGenericAdapter(): void
    {
        $asset = new ImageAsset('default', '/images/photo.jpg');

        $generator = new UrlGenerator(
            [],
            [new GenericUrlAdapter()],
            [
                'default' => [
                    'public_url_prefix' => '/media',
                ],
            ],
        );

        self::assertSame('/media/images/photo.jpg', $generator->generateAssetUrl($asset));
    }

    public function testCdnBuilderRegisteredWithStringKey(): void
    {
        $cdnBuilder = new class implements \Symfony\UX\Image\UrlGenerator\CdnUrlBuilderInterface {
            public function buildUrl(string $baseUrl, string $path, array $profileConfig, array $variantConfig): string
            {
                return 'https://keyed-cdn.test/'.$path;
            }

            public static function getProviderName(): string
            {
                return 'unused_name';
            }
        };

        $asset = new ImageAsset('storage', '/photo.jpg');

        $generator = new UrlGenerator(
            ['my_cdn' => $cdnBuilder],
            [new GenericUrlAdapter()],
            [
                'storage' => [
                    'cdn' => [
                        'provider' => 'my_cdn',
                        'base_url' => '',
                    ],
                ],
            ],
        );

        self::assertSame('https://keyed-cdn.test//photo.jpg', $generator->generateAssetUrl($asset));
    }

    public function testUrlAdapterRegisteredWithStringKey(): void
    {
        $adapter = new class implements UrlAdapterInterface {
            public function resolve(string $path, array $storageConfig, array $variantConfig = [], ?string $storageName = null): string
            {
                return '/string-keyed/'.$path;
            }

            public static function getName(): string
            {
                return 'unused_name';
            }
        };

        $asset = new ImageAsset('storage', '/photo.jpg');

        $generator = new UrlGenerator(
            [],
            ['custom_key' => $adapter],
            [
                'storage' => [
                    'url_adapter' => 'custom_key',
                ],
            ],
        );

        self::assertSame('/string-keyed//photo.jpg', $generator->generateAssetUrl($asset));
    }

    public function testMissingStorageUsesEmptyConfig(): void
    {
        $asset = new ImageAsset('unknown_storage', '/images/photo.jpg');

        $generator = new UrlGenerator(
            [],
            [new GenericUrlAdapter()],
            [],
        );

        // No storage config found, falls back to generic adapter with empty config
        self::assertSame('/images/photo.jpg', $generator->generateAssetUrl($asset));
    }
}
