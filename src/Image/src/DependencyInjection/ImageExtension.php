<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;
use Symfony\UX\Image\Provider\ProviderInterface;

/**
 * @author Aleksey Razbakov <aleksey@razbakov.com>
 */
final class ImageExtension extends Extension
{
    private bool $loadDefaultConfig = true;

    public function setLoadDefaultConfig(bool $load): void
    {
        $this->loadDefaultConfig = $load;
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        // Load services configuration
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.yaml');

        if ($this->loadDefaultConfig) {
            // Load default configuration using Yaml component
            $defaultConfigFile = __DIR__.'/../../config/ux_image.yaml';
            $defaultConfig = Yaml::parseFile($defaultConfigFile);

            // Merge default config with user configs
            $configs = array_merge([$defaultConfig['ux_image']], $configs);
        }

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        // Register the provider interface for autoconfiguration
        $container->registerForAutoconfiguration(ProviderInterface::class)
            ->addTag('ux.image.provider');

        // Set parameters
        $container->setParameter('ux_image.provider', $config['provider']);
        $container->setParameter('ux_image.missing_image_placeholder', $config['missing_image_placeholder']);
        $container->setParameter('ux_image.defaults', $config['defaults']);
        $container->setParameter('ux_image.providers', $config['providers']);
        $container->setParameter('ux_image.presets', $config['presets'] ?? []);
        $container->setParameter('ux_image.breakpoints', $config['breakpoints']);

        // Configure providers
        foreach ($config['providers'] as $name => $providerConfig) {
            $providerId = \sprintf('ux.image.provider.%s', $name);
            if ($container->hasDefinition($providerId)) {
                $providerDef = $container->getDefinition($providerId);
                $providerDef->addMethodCall('configure', [$providerConfig]);
            }
        }
    }

    public function getAlias(): string
    {
        return 'ux_image';
    }
}
