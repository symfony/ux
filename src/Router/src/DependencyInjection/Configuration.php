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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @experimental
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('ux_router');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
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

        return $treeBuilder;
    }
}
