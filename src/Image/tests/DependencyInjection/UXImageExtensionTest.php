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

use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Drivers\Vips\Driver as VipsDriver;
use Intervention\Image\Interfaces\ImageManagerInterface;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\UX\Image\Doctrine\ImageAssetType;
use Symfony\UX\Image\Processor\ImageDriverInterface;
use Symfony\UX\Image\Processor\ImageProcessorInterface;
use Symfony\UX\Image\Regeneration\RegenerationServiceResolver;
use Symfony\UX\Image\Storage\FlysystemStorage;
use Symfony\UX\Image\Storage\StorageInterface;
use Symfony\UX\Image\UXImageBundle;

#[CoversClass(UXImageBundle::class)]
final class UXImageExtensionTest extends TestCase
{
    #[Test]
    public function testGetAlias()
    {
        $extension = $this->getExtension();

        self::assertSame('ux_image', $extension->getAlias());
    }

    #[Test]
    public function testLoadWithDefaultConfig()
    {
        $container = $this->createContainer();
        $extension = $this->getExtension();

        $extension->load([], $container);

        self::assertSame('gd', $container->getParameter('ux_image.driver'));
        self::assertSame('100vw', $container->getParameter('ux_image.default_sizes'));
        self::assertSame(['avif', 'webp', 'jpeg', 'jpg', 'png'], $container->getParameter('ux_image.preferred_formats'));
    }

    #[Test]
    public function testLoadRejectsProfileThatExceedsConfiguredVariantLimit()
    {
        $container = $this->createContainer();

        self::expectException(InvalidConfigurationException::class);
        self::expectExceptionMessage('defines 2 variants; the configured limit is 1');

        $this->getExtension()->load([[
            'limits' => ['max_variants' => 1],
            'profiles' => [
                'avatar' => [
                    'formats' => ['jpeg'],
                    'variants' => [
                        'small' => ['width' => 100],
                        'large' => ['width' => 200],
                    ],
                ],
            ],
        ]], $container);
    }

    #[Test]
    public function testRegenerationResolverUsesExplicitProviderAndPersisterTags()
    {
        $container = $this->createContainer();
        $this->getExtension()->load([], $container);

        $definition = $container->getDefinition(RegenerationServiceResolver::class);
        self::assertInstanceOf(TaggedIteratorArgument::class, $definition->getArgument(0));
        self::assertSame('ux_image.regeneration.provider', $definition->getArgument(0)->getTag());
        self::assertInstanceOf(TaggedIteratorArgument::class, $definition->getArgument(1));
        self::assertSame('ux_image.regeneration.persister', $definition->getArgument(1)->getTag());
    }

    #[Test]
    public function testLoadAddsResponsiveDefaultProfile()
    {
        $container = $this->createContainer();
        $extension = $this->getExtension();

        $extension->load([], $container);

        $profiles = $container->getParameter('ux_image.profiles');
        self::assertIsArray($profiles);
        self::assertArrayHasKey('responsive_default', $profiles);

        $responsiveDefault = $profiles['responsive_default'];
        self::assertSame('immediate', $responsiveDefault['processing']);
        self::assertSame(['jpeg'], $responsiveDefault['formats']);
        self::assertArrayHasKey('mobile', $responsiveDefault['variants']);
        self::assertArrayHasKey('tablet', $responsiveDefault['variants']);
        self::assertArrayHasKey('desktop', $responsiveDefault['variants']);
        self::assertSame(640, $responsiveDefault['variants']['mobile']['width']);
        self::assertSame(1024, $responsiveDefault['variants']['tablet']['width']);
        self::assertSame(1920, $responsiveDefault['variants']['desktop']['width']);
    }

    #[Test]
    public function testLoadDoesNotOverrideExistingResponsiveDefault()
    {
        $container = $this->createContainer();
        $extension = $this->getExtension();

        $config = [
            'profiles' => [
                'responsive_default' => [
                    'processing' => 'deferred',
                    'formats' => ['webp'],
                    'variants' => [
                        'small' => [
                            'width' => 320,
                        ],
                    ],
                ],
            ],
        ];

        $extension->load([$config], $container);

        $profiles = $container->getParameter('ux_image.profiles');
        self::assertIsArray($profiles);
        self::assertArrayHasKey('responsive_default', $profiles);

        $responsiveDefault = $profiles['responsive_default'];
        self::assertSame('deferred', $responsiveDefault['processing']);
        self::assertSame(['webp'], $responsiveDefault['formats']);
        self::assertArrayHasKey('small', $responsiveDefault['variants']);
        self::assertArrayNotHasKey('mobile', $responsiveDefault['variants']);
    }

    #[Test]
    public function testLoadRejectsUnknownProcessingMode()
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->getExtension()->load([[
            'profiles' => [
                'invalid' => [
                    'processing' => 'later',
                    'formats' => ['jpeg'],
                ],
            ],
        ]], $this->createContainer());
    }

    #[Test]
    public function testLoadSetsAllParameters()
    {
        $container = $this->createContainer();
        $extension = $this->getExtension();

        $extension->load([], $container);

        self::assertTrue($container->hasParameter('ux_image.driver'));
        self::assertTrue($container->hasParameter('ux_image.default_sizes'));
        self::assertTrue($container->hasParameter('ux_image.preferred_formats'));
        self::assertTrue($container->hasParameter('ux_image.storages'));
        self::assertTrue($container->hasParameter('ux_image.profiles'));
        self::assertTrue($container->hasParameter('ux_image.cache'));
        self::assertTrue($container->hasParameter('ux_image.cache.enabled'));
        self::assertTrue($container->hasParameter('ux_image.cache.pool'));
        self::assertTrue($container->hasParameter('ux_image.cache.ttl'));

        self::assertSame('gd', $container->getParameter('ux_image.driver'));
        self::assertSame('100vw', $container->getParameter('ux_image.default_sizes'));
        self::assertSame(['avif', 'webp', 'jpeg', 'jpg', 'png'], $container->getParameter('ux_image.preferred_formats'));
        self::assertSame([], $container->getParameter('ux_image.storages'));
        self::assertIsArray($container->getParameter('ux_image.profiles'));

        $cache = $container->getParameter('ux_image.cache');
        self::assertIsArray($cache);
        self::assertFalse($cache['enabled']);
        self::assertSame('cache.app', $cache['pool']);
        self::assertSame(3600, $cache['ttl']);

        self::assertFalse($container->getParameter('ux_image.cache.enabled'));
        self::assertSame('cache.app', $container->getParameter('ux_image.cache.pool'));
        self::assertSame(3600, $container->getParameter('ux_image.cache.ttl'));
    }

    #[Test]
    public function testLoadWithCacheDisabledRemovesCachedUrlGenerator()
    {
        $container = $this->createContainer();
        $extension = $this->getExtension();

        // Cache is disabled by default
        $extension->load([], $container);

        self::assertFalse($container->hasDefinition('ux_image.url_generator.cached'));
    }

    #[Test]
    public function testLoadWithCacheEnabled()
    {
        $container = $this->createContainer();
        $extension = $this->getExtension();

        $config = [
            'cache' => [
                'enabled' => true,
                'pool' => 'cache.custom',
                'ttl' => 7200,
            ],
        ];

        $extension->load([$config], $container);

        self::assertTrue($container->hasDefinition('ux_image.url_generator.cached'));
        self::assertTrue($container->getParameter('ux_image.cache.enabled'));
        self::assertSame('cache.custom', $container->getParameter('ux_image.cache.pool'));
        self::assertSame(7200, $container->getParameter('ux_image.cache.ttl'));
    }

    // The Flysystem-absent guard is exercised for real by
    // Tests\Bundle\MissingOptionalDependencyTest::testFlysystemStorageThrowsWhenLibraryMissing,
    // which hides the package via process isolation. An in-process test here could
    // only ever be skipped (league/flysystem is in this package's require-dev).

    #[Test]
    public function testPrependWithoutFrameworkExtension()
    {
        $container = $this->createContainer();

        $bundle = new UXImageBundle();
        $extension = $bundle->getContainerExtension();
        $extension->prepend($container);

        $frameworkConfig = $container->getExtensionConfig('framework');

        self::assertEmpty($frameworkConfig);
    }

    #[Test]
    public function testPrependRegistersDoctrineTypeWhenDoctrineExtensionPresent()
    {
        $container = $this->createContainer();
        $container->registerExtension(new StubDoctrineExtension());
        $container->prependExtensionConfig('ux_image', ['doctrine_type' => true]);

        $bundle = new UXImageBundle();
        $bundle->getContainerExtension()->prepend($container);

        $doctrineConfig = $container->getExtensionConfig('doctrine');

        self::assertNotEmpty($doctrineConfig);

        $types = [];
        foreach ($doctrineConfig as $config) {
            if (isset($config['dbal']['types'])) {
                $types += $config['dbal']['types'];
            }
        }

        self::assertArrayHasKey('image_asset', $types);
        self::assertSame(ImageAssetType::class, $types['image_asset']);
    }

    #[Test]
    public function testPrependDoesNotRegisterDoctrineTypeWithoutOptIn()
    {
        $container = $this->createContainer();
        $container->registerExtension(new StubDoctrineExtension());

        new UXImageBundle()->getContainerExtension()->prepend($container);

        self::assertEmpty($container->getExtensionConfig('doctrine'));
    }

    #[Test]
    public function testPrependDoesNotRegisterDoctrineTypeWhenDoctrineExtensionAbsent()
    {
        $container = $this->createContainer();

        $bundle = new UXImageBundle();
        $bundle->getContainerExtension()->prepend($container);

        self::assertEmpty($container->getExtensionConfig('doctrine'));
    }

    #[Test]
    public function testPrependRejectsDoctrineTypeWithoutDoctrineBundle()
    {
        $container = $this->createContainer();
        $container->prependExtensionConfig('ux_image', ['doctrine_type' => true]);

        self::expectException(InvalidConfigurationException::class);
        self::expectExceptionMessage('doctrine/doctrine-bundle');

        new UXImageBundle()->getContainerExtension()->prepend($container);
    }

    #[Test]
    public function testLoadWithCustomDriver()
    {
        $container = $this->createContainer();
        $extension = $this->getExtension();

        $config = [
            'driver' => 'imagick',
        ];

        $extension->load([$config], $container);

        self::assertSame('imagick', $container->getParameter('ux_image.driver'));
    }

    #[Test]
    public function testLoadWithImagickDriverWiresImageManager()
    {
        if (!interface_exists(ImageManagerInterface::class)) {
            self::markTestSkipped('intervention/image is not installed.');
        }

        $container = $this->createContainer();
        $extension = $this->getExtension();

        $extension->load([['driver' => 'imagick']], $container);

        self::assertSame(ImagickDriver::class, $container->getDefinition('ux_image.image_manager')->getArgument(0));
    }

    #[Test]
    public function testLoadWithGdDriverBuildsGdImageManager()
    {
        if (!interface_exists(ImageManagerInterface::class)) {
            self::markTestSkipped('intervention/image is not installed.');
        }

        $container = $this->createContainer();
        $extension = $this->getExtension();

        $extension->load([], $container);

        self::assertSame(GdDriver::class, $container->getDefinition('ux_image.image_manager')->getArgument(0));
    }

    #[Test]
    public function testLoadWithVipsDriverThrowsWhenPackageMissing()
    {
        if (class_exists(VipsDriver::class)) {
            self::markTestSkipped('intervention/image-driver-vips is installed; cannot test the missing-package guard.');
        }

        $container = $this->createContainer();
        $extension = $this->getExtension();

        self::expectException(InvalidConfigurationException::class);
        self::expectExceptionMessageMatches('/intervention\/image-driver-vips/');

        $extension->load([['driver' => 'vips']], $container);
    }

    #[Test]
    public function testDriverServiceTakesPrecedenceOverDriver()
    {
        if (!interface_exists(ImageManagerInterface::class)) {
            self::markTestSkipped('intervention/image is not installed.');
        }

        $container = $this->createContainer();
        $extension = $this->getExtension();

        $extension->load([['driver' => 'gd', 'driver_service' => 'app.custom_driver']], $container);

        $driverArg = $container->getDefinition('ux_image.image_manager')->getArgument(0);
        self::assertInstanceOf(Reference::class, $driverArg);
        self::assertSame('app.custom_driver', (string) $driverArg);

        // A custom driver routes processing to the Intervention processor even when
        // the "driver" enum is left at its "gd" default.
        self::assertSame('imagick', $container->getDefinition('ux_image.processor.chain')->getArgument('$defaultDriver'));
    }

    #[Test]
    public function testProcessorServiceBypassesDriverSelection()
    {
        $container = $this->createContainer();
        $extension = $this->getExtension();

        $extension->load([[
            'driver' => 'acme',
            'processor_service' => 'app.image_processor',
        ]], $container);

        self::assertTrue($container->hasAlias(ImageProcessorInterface::class));
        self::assertSame('app.image_processor', (string) $container->getAlias(ImageProcessorInterface::class));
        self::assertSame('ux_image.processor.chain', (string) $container->getAlias(ImageDriverInterface::class));
        self::assertNull($container->getDefinition('ux_image.configuration_validator')->getArgument('$driver'));
    }

    #[Test]
    public function testLoadWithCustomStorages()
    {
        $container = $this->createContainer();
        $extension = $this->getExtension();

        $config = [
            'storages' => [
                'uploads' => [
                    'adapter_service' => 'app.custom_storage_adapter',
                    'public_url_prefix' => '/uploads',
                ],
            ],
        ];

        $extension->load([$config], $container);

        $storages = $container->getParameter('ux_image.storages');
        self::assertIsArray($storages);
        self::assertArrayHasKey('uploads', $storages);
        self::assertSame('app.custom_storage_adapter', $storages['uploads']['adapter_service']);
        self::assertSame('/uploads', $storages['uploads']['public_url_prefix']);
    }

    #[Test]
    public function testLoadWithoutConfiguredStoragesKeepsLocalStorageAlias()
    {
        $container = $this->createContainer();
        $extension = $this->getExtension();

        $extension->load([], $container);

        self::assertTrue($container->hasAlias(StorageInterface::class));
        self::assertSame('ux_image.storage.local', (string) $container->getAlias(StorageInterface::class));
        self::assertFalse($container->hasDefinition('ux_image.storage.router'));
    }

    #[Test]
    public function testLoadWithLocalOnlyStorageKeepsLocalAliasAndPreservesConfig()
    {
        $container = $this->createContainer();
        $extension = $this->getExtension();

        $config = [
            'storages' => [
                'assets' => [
                    'public_url_prefix' => 'https://cdn.example.com/assets',
                    'cdn' => [
                        'provider' => 'imgix',
                        'base_url' => 'https://example.imgix.net',
                    ],
                ],
            ],
        ];

        $extension->load([$config], $container);

        // No backend is registered for a local-only storage, so with no other
        // backend-bearing storage the plain LocalStorage alias stays in place.
        self::assertFalse($container->hasDefinition('ux_image.storage.router'));
        self::assertSame('ux_image.storage.local', (string) $container->getAlias(StorageInterface::class));

        // Its config still reaches the "ux_image.storages" parameter, which both
        // LocalStorage and UrlGenerator receive, so public_url_prefix/cdn are honored.
        $storages = $container->getParameter('ux_image.storages');
        self::assertIsArray($storages);
        self::assertArrayHasKey('assets', $storages);
        self::assertNull($storages['assets']['flysystem_service']);
        self::assertNull($storages['assets']['adapter_service']);
        self::assertSame('https://cdn.example.com/assets', $storages['assets']['public_url_prefix']);
        self::assertSame('imgix', $storages['assets']['cdn']['provider']);
    }

    #[Test]
    public function testLoadWithFlysystemStorageRegistersRoutedBackend()
    {
        if (!interface_exists(FilesystemOperator::class)) {
            self::markTestSkipped('league/flysystem is not installed.');
        }

        $container = $this->createContainer();
        $extension = $this->getExtension();

        $config = [
            'storages' => [
                'media' => [
                    'flysystem_service' => 'app.flysystem.media',
                    'public_url_prefix' => 'https://cdn.example.com',
                ],
            ],
        ];

        $extension->load([$config], $container);

        self::assertTrue($container->hasDefinition('ux_image.storage.flysystem.media'));
        $definition = $container->getDefinition('ux_image.storage.flysystem.media');
        self::assertSame(FlysystemStorage::class, $definition->getClass());
        self::assertInstanceOf(Reference::class, $definition->getArgument(0));
        self::assertSame('app.flysystem.media', (string) $definition->getArgument(0));
        self::assertSame('https://cdn.example.com', $definition->getArgument(1));

        self::assertTrue($container->hasDefinition('ux_image.storage.router'));
        self::assertSame('ux_image.storage.router', (string) $container->getAlias(StorageInterface::class));
        // The storage alias is an autowiring extension point, not a public,
        // fetch-by-id service (consistent with the other interface aliases).
        self::assertFalse($container->getAlias(StorageInterface::class)->isPublic());
    }

    #[Test]
    public function testLoadWithAdapterServiceRoutesThroughRouter()
    {
        $container = $this->createContainer();
        $extension = $this->getExtension();

        $config = [
            'storages' => [
                'uploads' => [
                    'adapter_service' => 'app.custom_storage_adapter',
                ],
            ],
        ];

        $extension->load([$config], $container);

        self::assertTrue($container->hasDefinition('ux_image.storage.router'));
        self::assertFalse($container->hasDefinition('ux_image.storage.flysystem.uploads'));
        self::assertSame('ux_image.storage.router', (string) $container->getAlias(StorageInterface::class));
    }

    private function getExtension(): ExtensionInterface
    {
        $bundle = new UXImageBundle();

        return $bundle->getContainerExtension();
    }

    private function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', sys_get_temp_dir());
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.build_dir', sys_get_temp_dir().'/build');
        $container->setParameter('kernel.debug', true);

        return $container;
    }
}

/**
 * Stub doctrine extension for testing prepend behavior.
 */
final class StubDoctrineExtension implements ExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
    }

    public function getNamespace(): string
    {
        return '';
    }

    public function getXsdValidationBasePath(): string|false
    {
        return false;
    }

    public function getAlias(): string
    {
        return 'doctrine';
    }
}
