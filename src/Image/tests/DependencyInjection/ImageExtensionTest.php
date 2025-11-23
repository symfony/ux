<?php

namespace Symfony\UX\Image\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\UX\Image\DependencyInjection\ImageExtension;
use Symfony\UX\Image\Provider\ProviderInterface;
use Symfony\UX\Image\Provider\ProviderRegistry;

class ImageExtensionTest extends TestCase
{
    private ContainerBuilder $container;
    private ImageExtension $extension;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->extension = new ImageExtension();
        $this->extension->setLoadDefaultConfig(false);
    }

    public function testLoadSetParameters(): void
    {
        $config = [
            'provider' => 'liip_imagine',
            'missing_image_placeholder' => '/path/to/404.jpg',
            'breakpoints' => ['sm' => 640],
            'defaults' => [
                'format' => 'webp',
                'quality' => 80,
                'loading' => 'lazy',
                'fetchpriority' => 'low',
                'fit' => 'cover',
                'placeholder' => 'none',
            ],
        ];

        $this->extension->load([$config], $this->container);

        $this->assertTrue($this->container->hasParameter('ux_image.provider'), 'Provider is not set');
        $this->assertTrue($this->container->hasParameter('ux_image.missing_image_placeholder'), 'Missing image placeholder is not set');
        $this->assertTrue($this->container->hasParameter('ux_image.defaults'), 'Defaults are not set');
        $this->assertTrue($this->container->hasParameter('ux_image.breakpoints'), 'Breakpoints are not set');

        $this->assertEquals('liip_imagine', $this->container->getParameter('ux_image.provider'));
        $this->assertEquals('/path/to/404.jpg', $this->container->getParameter('ux_image.missing_image_placeholder'));
        $this->assertEquals(['sm' => 640], $this->container->getParameter('ux_image.breakpoints'));
    }

    public function testLoadRegistersProviderRegistry(): void
    {
        $config = [
            'provider' => 'liip_imagine',
            'missing_image_placeholder' => '/path/to/404.jpg',
            'breakpoints' => ['sm' => 640],
            'defaults' => [
                'format' => 'webp',
                'quality' => 80,
                'loading' => 'lazy',
                'fetchpriority' => 'low',
                'fit' => 'cover',
                'placeholder' => 'none',
            ],
        ];

        $this->extension->load([$config], $this->container);

        $this->assertTrue($this->container->hasDefinition('ux.image.provider_registry'));

        $registryDef = $this->container->getDefinition('ux.image.provider_registry');
        $this->assertEquals(ProviderRegistry::class, $registryDef->getClass());
    }

    public function testLoadRegistersAutoconfigurationForProviders(): void
    {
        $config = [
            'provider' => 'liip_imagine',
            'missing_image_placeholder' => '/path/to/404.jpg',
            'breakpoints' => ['sm' => 640],
            'defaults' => [
                'format' => 'webp',
                'quality' => 80,
                'loading' => 'lazy',
                'fetchpriority' => 'low',
                'fit' => 'cover',
                'placeholder' => 'none',
            ],
        ];

        $this->extension->load([$config], $this->container);

        $autoconfigured = $this->container->getAutoconfiguredInstanceof();

        $this->assertArrayHasKey(ProviderInterface::class, $autoconfigured);
        $this->assertTrue($autoconfigured[ProviderInterface::class]->hasTag('ux.image.provider'));
    }

    public function testLoadWithProviders(): void
    {
        $config = [
            'provider' => 'liip_imagine',
            'missing_image_placeholder' => '/path/to/404.jpg',
            'breakpoints' => ['sm' => 640],
            'defaults' => [
                'format' => 'webp',
                'quality' => 80,
                'loading' => 'lazy',
                'fetchpriority' => 'low',
                'fit' => 'cover',
                'placeholder' => 'none',
            ],
            'providers' => [
                'liip_imagine' => [
                    'driver' => 'gd',
                    'cache' => 'default',
                ],
                'cloudinary' => [
                    'cloud_name' => 'test',
                    'api_key' => 'key',
                    'api_secret' => 'secret',
                ],
            ],
        ];

        $this->extension->load([$config], $this->container);

        $this->assertTrue($this->container->hasParameter('ux_image.providers'));

        $providers = $this->container->getParameter('ux_image.providers');
        $this->assertArrayHasKey('liip_imagine', $providers);
        $this->assertArrayHasKey('cloudinary', $providers);
    }

    public function testLoadWithPresets(): void
    {
        $config = [
            'provider' => 'liip_imagine',
            'missing_image_placeholder' => '/path/to/404.jpg',
            'breakpoints' => ['sm' => 640],
            'defaults' => [
                'format' => 'webp',
                'quality' => 80,
                'loading' => 'lazy',
                'fetchpriority' => 'low',
                'fit' => 'cover',
                'placeholder' => 'none',
            ],
            'presets' => [
                'thumbnail' => [
                    'width' => 200,
                    'height' => 200,
                    'fit' => 'cover',
                    'quality' => 90,
                ],
            ],
        ];

        $this->extension->load([$config], $this->container);

        $this->assertTrue($this->container->hasParameter('ux_image.presets'));

        $presets = $this->container->getParameter('ux_image.presets');
        $this->assertArrayHasKey('thumbnail', $presets);
        $this->assertEquals(200, $presets['thumbnail']['width']);
    }
}
