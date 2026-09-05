<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image;

use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Interfaces\ImageManagerInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\UX\Image\Doctrine\ImageAssetType;
use Symfony\UX\Image\Processor\ImageDriverCapabilities;
use Symfony\UX\Image\Processor\ImageProcessorInterface;
use Symfony\UX\Image\Storage\FlysystemStorage;
use Symfony\UX\Image\Storage\StorageInterface;
use Symfony\UX\Image\Storage\StorageRouter;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class UXImageBundle extends AbstractBundle
{
    /**
     * FQCN of the optional Intervention "vips" driver, referenced as a string so the
     * bundle does not require the intervention/image-driver-vips package to be installed.
     */
    private const VIPS_DRIVER_CLASS = 'Intervention\\Image\\Drivers\\Vips\\Driver';

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('driver')
                    ->cannotBeEmpty()
                    ->defaultValue('gd')
                    ->info('Transformation driver name. Built-in values are "gd" (default), "imagick" and "vips"; custom processors may support other names.')
                ->end()
                ->scalarNode('driver_service')
                    ->defaultNull()
                    ->info('Service id of a custom, pre-configured Intervention Image driver (Intervention\\Image\\Interfaces\\DriverInterface). When set, it takes precedence over "driver".')
                ->end()
                ->scalarNode('processor_service')
                    ->defaultNull()
                    ->info('Service id of an ImageProcessorInterface implementation. When set, it bypasses driver-based processor selection.')
                ->end()
                ->scalarNode('default_sizes')
                    ->defaultValue('100vw')
                    ->info('Default value for the "sizes" attribute')
                ->end()
                ->scalarNode('storage_root')
                    ->defaultValue('%kernel.project_dir%/var/ux-image')
                    ->info('Root directory for built-in local storages')
                ->end()
                ->booleanNode('doctrine_type')
                    ->defaultFalse()
                    ->info('Opt in to registering the global Doctrine "image_asset" DBAL type')
                ->end()
                ->arrayNode('preferred_formats')
                    ->scalarPrototype()->end()
                    ->defaultValue(['avif', 'webp', 'jpeg', 'jpg', 'png'])
                    ->info('Default format priority for rendering')
                ->end()
                ->arrayNode('cache')
                    ->addDefaultsIfNotSet()
                    ->info('Optional cache configuration for URL generation')
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultFalse()
                            ->info('Enable caching for URL generation')
                        ->end()
                        ->scalarNode('pool')
                            ->defaultValue('cache.app')
                            ->info('Cache pool service ID to use')
                        ->end()
                        ->integerNode('ttl')
                            ->defaultValue(3600)
                            ->info('Cache TTL in seconds (default: 1 hour)')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('limits')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('max_input_bytes')->min(1)->defaultValue(20_000_000)->end()
                        ->integerNode('max_width')->min(1)->defaultValue(12_000)->end()
                        ->integerNode('max_height')->min(1)->defaultValue(12_000)->end()
                        ->integerNode('max_megapixels')->min(1)->defaultValue(40)->end()
                        ->integerNode('max_variants')->min(1)->defaultValue(12)->end()
                        ->integerNode('max_output_megapixels')->min(1)->defaultValue(80)->end()
                    ->end()
                ->end()
                ->arrayNode('storages')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->validate()
                            ->ifTrue(static function (array $storage): bool {
                                $cdn = \is_array($storage['cdn'] ?? null) ? $storage['cdn'] : [];

                                return isset($cdn['provider']) && empty($cdn['base_url']);
                            })
                            ->thenInvalid('A "cdn.base_url" must be provided when configuring a CDN provider.')
                        ->end()
                        ->validate()
                            ->ifTrue(static fn (array $storage): bool => null !== ($storage['flysystem_service'] ?? null) && null !== ($storage['adapter_service'] ?? null))
                            ->thenInvalid('A storage cannot configure both "flysystem_service" and "adapter_service"; choose exactly one backend.')
                        ->end()
                        ->children()
                            ->scalarNode('flysystem_service')
                                ->defaultNull()
                                ->info('Service ID from league/flysystem-bundle')
                            ->end()
                            ->scalarNode('adapter_service')
                                ->defaultNull()
                                ->info('Custom storage adapter service id')
                            ->end()
                            ->scalarNode('url_adapter')
                                ->defaultValue('generic')
                                ->info('Name of the tagged URL adapter used for this storage')
                            ->end()
                            ->scalarNode('public_url_prefix')
                                ->defaultNull()
                                ->info('Public URL prefix for this storage')
                            ->end()
                            ->arrayNode('cdn')
                                ->info('Optional CDN integration for this storage')
                                ->children()
                                    ->scalarNode('provider')
                                        ->isRequired()
                                        ->cannotBeEmpty()
                                        ->info('Name of a tagged CDN URL builder (built-in: "cloudinary" or "imgix")')
                                    ->end()
                                    ->scalarNode('base_url')
                                        ->isRequired()
                                        ->info('Base URL for CDN transformations')
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->defaultValue([])
                ->end()
                ->arrayNode('profiles')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->validate()
                            ->ifTrue(static function (array $profile): bool {
                                return isset($profile['formats']) && \is_array($profile['formats']) && [] === $profile['formats'];
                            })
                            ->thenInvalid('At least one output format is required when defining a profile.')
                        ->end()
                        ->children()
                            ->enumNode('processing')
                                ->values(['immediate', 'deferred', 'async'])
                                ->defaultValue('immediate')
                                ->info('Variant processing mode: immediate, deferred or async')
                            ->end()
                            ->scalarNode('directory')
                                ->defaultNull()
                                ->info('Optional storage-relative directory for originals and generated variants')
                                ->validate()
                                    ->ifTrue(static function (mixed $directory): bool {
                                        if (null === $directory) {
                                            return false;
                                        }

                                        if (!\is_string($directory)) {
                                            return true;
                                        }

                                        try {
                                            new Storage\StoragePath($directory);

                                            return false;
                                        } catch (\InvalidArgumentException) {
                                            return true;
                                        }
                                    })
                                    ->thenInvalid('An image profile directory must be a safe, non-empty relative path.')
                                ->end()
                            ->end()
                            ->scalarNode('sizes')
                                ->defaultNull()
                                ->info('Default "sizes" attribute for this profile')
                            ->end()
                            ->arrayNode('preferred_formats')
                                ->scalarPrototype()->end()
                                ->info('Format priority for this profile')
                            ->end()
                            ->arrayNode('formats')
                                ->scalarPrototype()->end()
                                ->defaultValue(['webp', 'jpeg'])
                                ->info('Output formats to generate')
                            ->end()
                            ->arrayNode('variants')
                                ->useAttributeAsKey('name')
                                ->arrayPrototype()
                                    ->validate()
                                        ->ifTrue(static fn (array $variant): bool => !isset($variant['width']) && !isset($variant['height']))
                                        ->thenInvalid('An image variant must define at least a "width" or a "height".')
                                    ->end()
                                    ->children()
                                        ->integerNode('width')->min(1)->end()
                                        ->integerNode('height')->min(1)->end()
                                        ->enumNode('mode')
                                            ->values(['crop', 'fit', 'fill'])
                                            ->defaultValue('fit')
                                        ->end()
                                        ->integerNode('quality')
                                            ->min(1)
                                            ->max(100)
                                            ->defaultValue(80)
                                        ->end()
                                        ->scalarNode('density')
                                            ->info('Density descriptor for srcset (e.g., "1x", "2x", "3x")')
                                            ->defaultNull()
                                        ->end()
                                        ->scalarNode('media')
                                            ->info('Media query for art direction (e.g., "(max-width: 768px)")')
                                            ->defaultNull()
                                        ->end()
                                        ->scalarNode('position')
                                            ->info('Crop position (top, center, bottom, left, right, or "50% 30%")')
                                            ->defaultValue('center')
                                        ->end()
                                    ->end()
                                ->end()
                                ->defaultValue([])
                            ->end()
                        ->end()
                    ->end()
                    ->defaultValue([])
                ->end()
            ->end()
        ;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // Inject the built-in responsive_default profile when the user has not defined it.
        /** @var array<string, array<string, mixed>> $profiles */
        $profiles = \is_array($config['profiles'] ?? null) ? $config['profiles'] : [];
        if (!isset($profiles['responsive_default'])) {
            $profiles['responsive_default'] = [
                'processing' => 'immediate',
                // JPEG is the only codec guaranteed by every supported GD
                // installation. Applications opt into modern codecs explicitly,
                // and those profiles are validated against driver capabilities.
                'formats' => ['jpeg'],
                'variants' => [
                    'mobile' => ['width' => 640, 'mode' => 'fit', 'quality' => 80],
                    'tablet' => ['width' => 1024, 'mode' => 'fit', 'quality' => 85],
                    'desktop' => ['width' => 1920, 'mode' => 'fit', 'quality' => 90],
                ],
            ];
        }
        $config['profiles'] = $profiles;
        /** @var array{max_input_bytes: int, max_width: int, max_height: int, max_megapixels: int, max_variants: int, max_output_megapixels: int} $limits */
        $limits = $config['limits'];
        foreach ($profiles as $profileName => $profileConfig) {
            $variants = \is_array($profileConfig['variants'] ?? null) ? $profileConfig['variants'] : [];
            if (\count($variants) > $limits['max_variants']) {
                throw new InvalidConfigurationException(\sprintf('Image profile "%s" defines %d variants; the configured limit is %d.', $profileName, \count($variants), $limits['max_variants']));
            }
            $formats = \is_array($profileConfig['formats'] ?? null) ? $profileConfig['formats'] : [];
            $outputPixels = 0;
            foreach ($variants as $variant) {
                if (!\is_array($variant)) {
                    continue;
                }
                $width = \is_int($variant['width'] ?? null) ? $variant['width'] : 0;
                $height = \is_int($variant['height'] ?? null) ? $variant['height'] : 0;
                $outputPixels += $width * $height * \count($formats);
            }
            if ($outputPixels > $limits['max_output_megapixels'] * 1_000_000) {
                throw new InvalidConfigurationException(\sprintf('Image profile "%s" requires %d output pixels; the configured limit is %d.', $profileName, $outputPixels, $limits['max_output_megapixels'] * 1_000_000));
            }
        }

        /** @var array<string, array<string, mixed>> $storages */
        $storages = $config['storages'];

        if (array_reduce($storages, static fn (bool $carry, array $s): bool => $carry || null !== $s['flysystem_service'], false)) {
            if (!interface_exists(\League\Flysystem\FilesystemOperator::class)) {
                throw new InvalidConfigurationException('Configuring storages that rely on Flysystem requires installing league/flysystem. Either install the suggested dependency or switch to a custom adapter_service.');
            }
        }

        $driver = \is_string($config['driver'] ?? null) ? $config['driver'] : 'gd';
        $driverService = \is_string($config['driver_service'] ?? null) ? $config['driver_service'] : null;
        $processorService = \is_string($config['processor_service'] ?? null) ? $config['processor_service'] : null;
        if ('gd' === $driver && null === $driverService && null === $processorService) {
            if (!\extension_loaded('gd')) {
                throw new InvalidConfigurationException('The default "gd" image driver requires the PHP GD extension. Install/enable ext-gd, configure another driver, or set "ux_image.processor_service".');
            }
            $capabilities = ImageDriverCapabilities::gd();
            foreach (array_keys($profiles) as $profileName) {
                $configuredFormats = $profiles[$profileName]['formats'] ?? [];
                $formats = [];
                if (\is_array($configuredFormats)) {
                    foreach ($configuredFormats as $format) {
                        if (\is_string($format)) {
                            $formats[] = $format;
                        }
                    }
                }
                $capabilities->assertEncodable($formats, $profileName);
            }
        }

        // Fail fast at container-compile time when the configured Intervention driver
        // cannot be satisfied, mirroring the Flysystem guard above.
        if (null !== $processorService) {
            // A custom processor owns its dependencies and driver support.
        } elseif (null !== $driverService) {
            if (!interface_exists(ImageManagerInterface::class)) {
                throw new InvalidConfigurationException('Configuring "ux_image.driver_service" requires the "intervention/image" package. Try running "composer require intervention/image".');
            }
        } elseif ('imagick' === $driver || 'vips' === $driver) {
            if (!interface_exists(ImageManagerInterface::class)) {
                throw new InvalidConfigurationException(\sprintf('The "%s" image driver requires the "intervention/image" package. Try running "composer require intervention/image".', $driver));
            }
            if ('vips' === $driver && !class_exists(self::VIPS_DRIVER_CLASS)) {
                throw new InvalidConfigurationException('The "vips" image driver requires the "intervention/image-driver-vips" package (which also needs the libvips system library and the ext-ffi PHP extension). Try running "composer require intervention/image-driver-vips".');
            }
        }

        $container->import('../config/services.php');
        $bundles = $builder->hasParameter('kernel.bundles') ? $builder->getParameter('kernel.bundles') : [];
        if (\is_array($bundles) && isset($bundles['TwigComponentBundle'])) {
            $container->import('../config/twig_component.php');
        }

        if (null !== $processorService) {
            $builder->setAlias(ImageProcessorInterface::class, $processorService);
            $builder->getDefinition('ux_image.configuration_validator')->replaceArgument('$driver', null);
        }

        // Wire the Intervention ImageManager for the driver (or a custom driver service,
        // which takes precedence). The manager service only exists when intervention/image
        // is installed; a custom driver_service always routes to the Intervention processor.
        if (interface_exists(ImageManagerInterface::class)) {
            $builder->getDefinition('ux_image.image_manager')->replaceArgument(0, null !== $driverService
                ? new Reference($driverService)
                : match ($driver) {
                    'imagick' => ImagickDriver::class,
                    'vips' => self::VIPS_DRIVER_CLASS,
                    default => GdDriver::class,
                });

            if (null !== $driverService) {
                $builder->getDefinition('ux_image.processor.chain')->replaceArgument('$defaultDriver', 'imagick');
            }
        }

        // Build a per-name backend for every configured storage: a FlysystemStorage
        // wrapping the referenced Flysystem filesystem, or the referenced custom adapter.
        // Names without a configured backend fall back to LocalStorage via the router.
        $storageBackends = [];
        foreach ($storages as $name => $storage) {
            $flysystemService = \is_string($storage['flysystem_service'] ?? null) ? $storage['flysystem_service'] : null;
            $adapterService = \is_string($storage['adapter_service'] ?? null) ? $storage['adapter_service'] : null;

            if (null !== $flysystemService) {
                $prefix = \is_string($storage['public_url_prefix'] ?? null) ? $storage['public_url_prefix'] : '';
                $definitionId = 'ux_image.storage.flysystem.'.$name;
                $builder->setDefinition($definitionId, new Definition(FlysystemStorage::class, [
                    new Reference($flysystemService),
                    $prefix,
                    new Reference(ProcessingLimits::class),
                ]));
                $storageBackends[$name] = new Reference($definitionId);
            } elseif (null !== $adapterService) {
                $storageBackends[$name] = new Reference($adapterService);
            }
        }

        if ([] !== $storageBackends) {
            $builder->setDefinition('ux_image.storage.router', new Definition(StorageRouter::class, [
                ServiceLocatorTagPass::register($builder, $storageBackends),
                new Reference('ux_image.storage.local'),
            ]));
            $builder->setAlias(StorageInterface::class, 'ux_image.storage.router');
        }

        /** @var array{enabled: bool, pool: string, ttl: int} $cache */
        $cache = $config['cache'];

        $container->parameters()
            ->set('ux_image.driver', $config['driver'])
            ->set('ux_image.storage_root', $config['storage_root'])
            ->set('ux_image.default_sizes', $config['default_sizes'])
            ->set('ux_image.preferred_formats', $config['preferred_formats'])
            ->set('ux_image.storages', $storages)
            ->set('ux_image.profiles', $config['profiles'])
            ->set('ux_image.limits', $limits)
            ->set('ux_image.limits.max_input_bytes', $limits['max_input_bytes'])
            ->set('ux_image.limits.max_width', $limits['max_width'])
            ->set('ux_image.limits.max_height', $limits['max_height'])
            ->set('ux_image.limits.max_pixels', $limits['max_megapixels'] * 1_000_000)
            ->set('ux_image.limits.max_variants', $limits['max_variants'])
            ->set('ux_image.limits.max_output_pixels', $limits['max_output_megapixels'] * 1_000_000)
            ->set('ux_image.cache', $cache)
            ->set('ux_image.cache.enabled', $cache['enabled'])
            ->set('ux_image.cache.pool', $cache['pool'])
            ->set('ux_image.cache.ttl', $cache['ttl'])
            ->set('ux_image.cache.namespace', hash('sha256', serialize($storages)))
        ;

        if (!$cache['enabled']) {
            $builder->removeDefinition('ux_image.url_generator.cached');
        }
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // Register the ImageAssetType only when DoctrineBundle is installed, so the bundle
        // does not require doctrine/dbal. When absent, the "doctrine" extension is missing
        // and nothing here runs (ImageAssetType is never autoloaded).
        $bundleConfigs = $builder->getExtensionConfig('ux_image');
        $doctrineTypeEnabled = false;
        foreach ($bundleConfigs as $bundleConfig) {
            if (true === ($bundleConfig['doctrine_type'] ?? false)) {
                $doctrineTypeEnabled = true;
            }
        }
        if ($doctrineTypeEnabled && !$builder->hasExtension('doctrine')) {
            throw new InvalidConfigurationException('Enabling "ux_image.doctrine_type" requires DoctrineBundle. Try running "composer require doctrine/doctrine-bundle".');
        }
        if ($doctrineTypeEnabled) {
            $builder->prependExtensionConfig('doctrine', [
                'dbal' => [
                    'types' => [
                        ImageAssetType::NAME => ImageAssetType::class,
                    ],
                ],
            ]);
        }
    }
}
