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
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\UX\Image\Provider\PassThroughProvider;
use Symfony\UX\Image\Provider\ProviderInterface;
use Symfony\UX\Image\Provider\ProviderRegistry;
use Symfony\UX\Image\Provider\UrlPatternProvider;
use Symfony\UX\Image\Service\Transformer;
use Symfony\UX\Image\Twig\ImageRuntime;

/**
 * @author Aleksey Razbakov <aleksey@razbakov.com>
 */
class ImageExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.yaml');

        // Register breakpoints
        $container->setParameter('ux_image.breakpoints', $config['breakpoints']);

        // Register defaults
        $container->setParameter('ux_image.defaults', $config['defaults']);

        // Configure Transformer with breakpoints
        $container->getDefinition(Transformer::class)
            ->setArgument('$breakpoints', $config['breakpoints']);

        // Register PassThroughProvider as default
        $container->register(PassThroughProvider::class, PassThroughProvider::class)
            ->addTag('ux_image.provider', ['name' => 'passthrough']);

        // Register UrlPatternProvider if configured
        if (isset($config['providers']['url_pattern'])) {
            $container->register(UrlPatternProvider::class, UrlPatternProvider::class)
                ->addTag('ux_image.provider', ['name' => 'url_pattern'])
                ->addMethodCall('configure', [$config['providers']['url_pattern']]);
        }

        // Register custom providers from config
        foreach ($config['providers'] as $name => $providerConfig) {
            if ('url_pattern' === $name) {
                continue; // Already handled above
            }

            if (isset($providerConfig['pattern'])) {
                $container->register('ux_image.provider.'.$name, UrlPatternProvider::class)
                    ->addTag('ux_image.provider', ['name' => $name])
                    ->addMethodCall('configure', [$providerConfig]);
            }
        }

        // Set default provider
        $container->getDefinition(ProviderRegistry::class)
            ->addMethodCall('setDefaultProvider', [$config['default_provider']]);

        // Register ImageRuntime as a Twig extension
        $container->register(ImageRuntime::class, ImageRuntime::class)
            ->addArgument($container->getDefinition(ProviderRegistry::class))
            ->addArgument($container->getDefinition(Transformer::class))
            ->addArgument($config['defaults'])
            ->addTag('twig.extension');
    }

    public function getAlias(): string
    {
        return 'ux_image';
    }
}
