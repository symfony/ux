<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Router\DependencyInjection;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\UX\Router\EventListener\RefreshRoutesListener;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 *
 * @experimental
 */
class UXRouterExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = (new PhpFileLoader($container, new FileLocator(\dirname(__DIR__).'/../config')));
        $loader->load('services.php');

        $cacheWarmerDefinition = $container->getDefinition('ux.router.cache_warmer.routes_cache_warmer');
        $cacheWarmerDefinition->setArgument(2, $config['dump_directory']);
        $cacheWarmerDefinition->setArgument(3, $config['dump_typescript']);
        $cacheWarmerDefinition->setArgument(4, $config['routes']);

        if ($container->getParameter('kernel.debug')) {
            $container->register('ux.router.event_listener.refresh_routes', RefreshRoutesListener::class)
                ->setArguments([
                    new Reference('router'),
                    new Reference('ux.router.routes_dumper'),
                    new Reference('config_cache_factory'),
                    '%kernel.cache_dir%',
                    $config['dump_directory'],
                    $config['dump_typescript'],
                    $config['routes'],
                ])
                // After the AssetMapper dev server (35), which serves assets on its own, and before the router (32)
                ->addTag('kernel.event_listener', ['event' => KernelEvents::REQUEST, 'priority' => 33]);
        }
    }

    public function prepend(ContainerBuilder $container): void
    {
        if (!$this->isAssetMapperAvailable($container)) {
            return;
        }

        $dumpDirectory = $this->processConfiguration(new Configuration(), $container->getExtensionConfig('ux_router'))['dump_directory'];

        $container->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    __DIR__.'/../../assets/dist' => '@symfony/ux-router',
                    $dumpDirectory => 'var/routes',
                ],
            ],
        ]);
    }

    private function isAssetMapperAvailable(ContainerBuilder $container): bool
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return false;
        }

        // Before Symfony 8.2, FrameworkBundle provided the AssetMapper configuration.
        $bundlesMetadata = $container->getParameter('kernel.bundles_metadata');
        if (!isset($bundlesMetadata['FrameworkBundle'])) {
            return false;
        }

        return isset($bundlesMetadata['AssetMapperBundle'])
            || is_file($bundlesMetadata['FrameworkBundle']['path'].'/Resources/config/asset_mapper.php');
    }
}
