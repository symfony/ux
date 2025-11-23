<?php

namespace Symfony\UX\Image\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\UX\Image\DependencyInjection\Configuration;

class ConfigurationTest extends TestCase
{
    private Configuration $configuration;
    private Processor $processor;

    protected function setUp(): void
    {
        $this->configuration = new Configuration();
        $this->processor = new Processor();
    }

    public function testDefaultValues(): void
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

        $processedConfig = $this->processor->processConfiguration(
            $this->configuration,
            [$config]
        );

        $this->assertEquals('liip_imagine', $processedConfig['provider']);
        $this->assertEquals('/path/to/404.jpg', $processedConfig['missing_image_placeholder']);
        $this->assertEquals(['sm' => 640], $processedConfig['breakpoints']);
    }

    public function testRequiredValues(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $config = [];
        $this->processor->processConfiguration($this->configuration, [$config]);
    }

    public function testInvalidFormat(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $config = [
            'provider' => 'liip_imagine',
            'missing_image_placeholder' => '/path/to/404.jpg',
            'breakpoints' => ['sm' => 640],
            'defaults' => [
                'format' => 'invalid',  // Invalid format
                'quality' => 80,
                'loading' => 'lazy',
                'fetchpriority' => 'low',
                'fit' => 'cover',
                'placeholder' => 'none',
            ],
        ];

        $this->processor->processConfiguration($this->configuration, [$config]);
    }

    public function testInvalidQuality(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $config = [
            'provider' => 'liip_imagine',
            'missing_image_placeholder' => '/path/to/404.jpg',
            'breakpoints' => ['sm' => 640],
            'defaults' => [
                'format' => 'webp',
                'quality' => 101,  // Invalid quality (> 100)
                'loading' => 'lazy',
                'fetchpriority' => 'low',
                'fit' => 'cover',
                'placeholder' => 'none',
            ],
        ];

        $this->processor->processConfiguration($this->configuration, [$config]);
    }

    public function testValidProviders(): void
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

        $processedConfig = $this->processor->processConfiguration(
            $this->configuration,
            [$config]
        );

        $this->assertArrayHasKey('liip_imagine', $processedConfig['providers']);
        $this->assertArrayHasKey('cloudinary', $processedConfig['providers']);
    }

    public function testValidPresets(): void
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
                'hero' => [
                    'ratio' => '16:9',
                    'width' => '100vw',
                    'loading' => 'eager',
                    'fetchpriority' => 'high',
                ],
            ],
        ];

        $processedConfig = $this->processor->processConfiguration(
            $this->configuration,
            [$config]
        );

        $this->assertArrayHasKey('thumbnail', $processedConfig['presets']);
        $this->assertArrayHasKey('hero', $processedConfig['presets']);
        $this->assertEquals(200, $processedConfig['presets']['thumbnail']['width']);
        $this->assertEquals('16:9', $processedConfig['presets']['hero']['ratio']);
    }
}
