<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\UX\Image\UXImageBundle;

#[CoversClass(UXImageBundle::class)]
final class ConfigurationTest extends TestCase
{
    public function testDefaultConfiguration()
    {
        $config = $this->processConfiguration([]);

        self::assertSame('gd', $config['driver']);
        self::assertSame('100vw', $config['default_sizes']);
        self::assertSame(['avif', 'webp', 'jpeg', 'jpg', 'png'], $config['preferred_formats']);
        self::assertSame([], $config['storages']);
        self::assertSame([], $config['profiles']);
        self::assertFalse($config['doctrine_type']);
        self::assertFalse($config['cache']['enabled']);
        self::assertSame('cache.app', $config['cache']['pool']);
        self::assertSame(3600, $config['cache']['ttl']);
        self::assertSame([
            'max_input_bytes' => 20_000_000,
            'max_width' => 12_000,
            'max_height' => 12_000,
            'max_megapixels' => 40,
            'max_variants' => 12,
            'max_output_megapixels' => 80,
        ], $config['limits']);
    }

    public function testProcessingLimitsAreConfigurableAndPositive()
    {
        $config = $this->processConfiguration(['limits' => ['max_input_bytes' => 1234, 'max_variants' => 3]]);

        self::assertSame(1234, $config['limits']['max_input_bytes']);
        self::assertSame(3, $config['limits']['max_variants']);

        $this->expectException(InvalidConfigurationException::class);
        $this->processConfiguration(['limits' => ['max_width' => 0]]);
    }

    public function testDriverAcceptsGd()
    {
        $config = $this->processConfiguration(['driver' => 'gd']);

        self::assertSame('gd', $config['driver']);
    }

    public function testDriverAcceptsImagick()
    {
        $config = $this->processConfiguration(['driver' => 'imagick']);

        self::assertSame('imagick', $config['driver']);
    }

    public function testDriverAcceptsVips()
    {
        $config = $this->processConfiguration(['driver' => 'vips']);

        self::assertSame('vips', $config['driver']);
    }

    public function testDriverAcceptsCustomProcessorName()
    {
        $config = $this->processConfiguration(['driver' => 'acme']);

        self::assertSame('acme', $config['driver']);
    }

    public function testDriverServiceDefaultsToNull()
    {
        $config = $this->processConfiguration([]);

        self::assertNull($config['driver_service']);
    }

    public function testDriverServiceCanBeSet()
    {
        $config = $this->processConfiguration(['driver_service' => 'app.custom_driver']);

        self::assertSame('app.custom_driver', $config['driver_service']);
    }

    public function testProcessorServiceCanBeSet()
    {
        $config = $this->processConfiguration(['processor_service' => 'app.image_processor']);

        self::assertSame('app.image_processor', $config['processor_service']);
    }

    public function testProfileConfiguration()
    {
        $config = $this->processConfiguration([
            'profiles' => [
                'avatar' => [
                    'directory' => 'products/avatars',
                    'formats' => ['webp', 'jpeg'],
                    'variants' => [
                        'thumb' => [
                            'width' => 100,
                            'height' => 100,
                            'mode' => 'crop',
                            'quality' => 90,
                        ],
                    ],
                ],
            ],
        ]);

        self::assertArrayHasKey('avatar', $config['profiles']);
        self::assertSame('products/avatars', $config['profiles']['avatar']['directory']);
        self::assertSame('immediate', $config['profiles']['avatar']['processing']);
        self::assertSame(['webp', 'jpeg'], $config['profiles']['avatar']['formats']);
        self::assertSame(100, $config['profiles']['avatar']['variants']['thumb']['width']);
        self::assertSame(100, $config['profiles']['avatar']['variants']['thumb']['height']);
        self::assertSame('crop', $config['profiles']['avatar']['variants']['thumb']['mode']);
        self::assertSame(90, $config['profiles']['avatar']['variants']['thumb']['quality']);
    }

    public function testUnsafeProfileDirectoryIsRejected()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('safe, non-empty relative path');

        $this->processConfiguration([
            'profiles' => [
                'broken' => [
                    'directory' => '../public',
                ],
            ],
        ]);
    }

    public function testProfileVariantDefaults()
    {
        $config = $this->processConfiguration([
            'profiles' => [
                'gallery' => [
                    'formats' => ['webp'],
                    'variants' => [
                        'mobile' => [
                            'width' => 640,
                        ],
                    ],
                ],
            ],
        ]);

        $variant = $config['profiles']['gallery']['variants']['mobile'];
        self::assertSame('fit', $variant['mode']);
        self::assertSame(80, $variant['quality']);
        self::assertSame('center', $variant['position']);
        self::assertNull($variant['density']);
        self::assertNull($variant['media']);
    }

    public function testProfileWithEmptyFormatsIsRejected()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('At least one output format is required');

        $this->processConfiguration([
            'profiles' => [
                'broken' => [
                    'formats' => [],
                    'variants' => [],
                ],
            ],
        ]);
    }

    public function testProfileVariantRequiresAtLeastOnePositiveDimension()
    {
        $rejections = 0;
        foreach ([[], ['width' => 0], ['height' => -1]] as $variant) {
            try {
                $this->processConfiguration([
                    'profiles' => [
                        'broken' => [
                            'variants' => ['invalid' => $variant],
                        ],
                    ],
                ]);
                self::fail('Invalid variant dimensions should be rejected.');
            } catch (InvalidConfigurationException) {
                ++$rejections;
            }
        }

        self::assertSame(3, $rejections);
    }

    public function testStorageCanBeDeclaredLocalOnly()
    {
        // A storage declared with neither "flysystem_service" nor "adapter_service"
        // is valid: it is backed by local storage, and its "public_url_prefix"/"cdn"
        // config is preserved so the URL layer can honor it.
        $config = $this->processConfiguration([
            'storages' => [
                'assets' => [
                    'public_url_prefix' => 'https://cdn.example.com/assets',
                    'cdn' => [
                        'provider' => 'imgix',
                        'base_url' => 'https://example.imgix.net',
                    ],
                ],
            ],
        ]);

        self::assertArrayHasKey('assets', $config['storages']);
        self::assertNull($config['storages']['assets']['flysystem_service']);
        self::assertNull($config['storages']['assets']['adapter_service']);
        self::assertSame('generic', $config['storages']['assets']['url_adapter']);
        self::assertSame('https://cdn.example.com/assets', $config['storages']['assets']['public_url_prefix']);
        self::assertSame('imgix', $config['storages']['assets']['cdn']['provider']);
        self::assertSame('https://example.imgix.net', $config['storages']['assets']['cdn']['base_url']);
    }

    public function testStorageWithFlysystemService()
    {
        $config = $this->processConfiguration([
            'storages' => [
                'media' => [
                    'flysystem_service' => 's3.storage',
                    'public_url_prefix' => '/uploads',
                ],
            ],
        ]);

        self::assertSame('s3.storage', $config['storages']['media']['flysystem_service']);
        self::assertSame('/uploads', $config['storages']['media']['public_url_prefix']);
    }

    public function testStorageWithAdapterService()
    {
        $config = $this->processConfiguration([
            'storages' => [
                'custom' => [
                    'adapter_service' => 'app.custom_storage',
                ],
            ],
        ]);

        self::assertSame('app.custom_storage', $config['storages']['custom']['adapter_service']);
    }

    public function testStorageRejectsTwoBackends()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('cannot configure both');

        $this->processConfiguration([
            'storages' => [
                'custom' => [
                    'flysystem_service' => 'app.flysystem',
                    'adapter_service' => 'app.custom_storage',
                ],
            ],
        ]);
    }

    public function testStorageUrlAdapterIsIndependentFromStorageAdapter()
    {
        $config = $this->processConfiguration([
            'storages' => [
                'custom' => [
                    'adapter_service' => 'app.custom_storage',
                    'url_adapter' => 'signed',
                ],
            ],
        ]);

        self::assertSame('app.custom_storage', $config['storages']['custom']['adapter_service']);
        self::assertSame('signed', $config['storages']['custom']['url_adapter']);
    }

    public function testStorageCdnRequiresBaseUrl()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('cdn.base_url');

        $this->processConfiguration([
            'storages' => [
                'cdn' => [
                    'flysystem_service' => 'default',
                    'cdn' => [
                        'provider' => 'cloudinary',
                        'base_url' => '',
                    ],
                ],
            ],
        ]);
    }

    public function testStorageCdnWithValidConfig()
    {
        $config = $this->processConfiguration([
            'storages' => [
                'cdn' => [
                    'flysystem_service' => 'default',
                    'cdn' => [
                        'provider' => 'imgix',
                        'base_url' => 'https://example.imgix.net',
                    ],
                ],
            ],
        ]);

        self::assertSame('imgix', $config['storages']['cdn']['cdn']['provider']);
        self::assertSame('https://example.imgix.net', $config['storages']['cdn']['cdn']['base_url']);
    }

    public function testStorageCdnAcceptsCustomBuilderName()
    {
        $config = $this->processConfiguration([
            'storages' => [
                'cdn' => [
                    'cdn' => [
                        'provider' => 'bunny',
                        'base_url' => 'https://example.b-cdn.net',
                    ],
                ],
            ],
        ]);

        self::assertSame('bunny', $config['storages']['cdn']['cdn']['provider']);
    }

    public function testCacheConfiguration()
    {
        $config = $this->processConfiguration([
            'cache' => [
                'enabled' => true,
                'pool' => 'cache.redis',
                'ttl' => 7200,
            ],
        ]);

        self::assertTrue($config['cache']['enabled']);
        self::assertSame('cache.redis', $config['cache']['pool']);
        self::assertSame(7200, $config['cache']['ttl']);
    }

    public function testPreferredFormatsOverride()
    {
        $config = $this->processConfiguration([
            'preferred_formats' => ['webp', 'png'],
        ]);

        self::assertSame(['webp', 'png'], $config['preferred_formats']);
    }

    private function processConfiguration(array $input): array
    {
        $bundle = new UXImageBundle();
        $extension = $bundle->getContainerExtension();
        $container = new ContainerBuilder();

        $configuration = $extension->getConfiguration([], $container);
        $processor = new Processor();

        return $processor->processConfiguration($configuration, ['ux_image' => $input]);
    }
}
