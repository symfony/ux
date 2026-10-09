<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Router;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Config\Definition\Configuration;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\UX\Router\EventListener\RefreshRoutesListener;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @final
 *
 * @experimental
 */
class UXRouterBundle extends AbstractBundle
{
    protected string $extensionAlias = 'ux_router';

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('dump_directory')
                    ->info('The directory where routes and TypeScript types are dumped.')
                    ->defaultValue('%kernel.project_dir%/var/routes')
                ->end()
                ->booleanNode('dump_typescript')
                    ->info('Control whether TypeScript types are dumped alongside routes. Disable this if you do not use TypeScript (e.g. in production when using AssetMapper).')
                    ->defaultTrue()
                ->end()
                ->arrayNode('routes')
                    ->info('Route name patterns to expose to JavaScript. Prefix with a `!` to exclude a pattern. Supports wildcards (e.g., `app_*`, `!app_admin_*`). The `expose` route option takes precedence over patterns.')
                    ->scalarPrototype()->end()
                    ->beforeNormalization()
                        ->ifString()
                        ->then(static fn ($v) => [$v])
                    ->end()
                ->end()
            ->end()
        ;
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if (!$this->isAssetMapperAvailable($builder)) {
            return;
        }

        $configuration = new Configuration($this, $builder, $this->extensionAlias);
        $config = new Processor()->processConfiguration($configuration, $builder->getExtensionConfig($this->extensionAlias));

        $builder->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    __DIR__.'/../assets/dist' => '@symfony/ux-router',
                    $config['dump_directory'] => 'var/routes',
                ],
            ],
        ]);
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

        $container->services()
            ->get('ux.router.cache_warmer.routes_cache_warmer')
                ->arg(2, $config['dump_directory'])
                ->arg(3, $config['dump_typescript'])
                ->arg(4, $config['routes'])
        ;

        if ($builder->getParameter('kernel.debug')) {
            $container->services()
                ->set('ux.router.event_listener.refresh_routes', RefreshRoutesListener::class)
                    ->args([
                        service('router'),
                        service('ux.router.routes_dumper'),
                        service('config_cache_factory'),
                        '%kernel.cache_dir%',
                        $config['dump_directory'],
                        $config['dump_typescript'],
                        $config['routes'],
                    ])
                    // After the AssetMapper dev server (35), which serves assets on its own, and before the router (32)
                    ->tag('kernel.event_listener', ['event' => KernelEvents::REQUEST, 'priority' => 33])
            ;
        }
    }

    private function isAssetMapperAvailable(ContainerBuilder $builder): bool
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return false;
        }

        // Before Symfony 8.2, FrameworkBundle provided the AssetMapper configuration.
        $bundlesMetadata = $builder->getParameter('kernel.bundles_metadata');
        if (!isset($bundlesMetadata['FrameworkBundle'])) {
            return false;
        }

        return isset($bundlesMetadata['AssetMapperBundle'])
            || is_file($bundlesMetadata['FrameworkBundle']['path'].'/Resources/config/asset_mapper.php');
    }
}
