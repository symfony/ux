<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @internal
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('turbo');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('streams')
                    ->info('Enable this section to use Turbo Streams')
                    ->children()
                        ->scalarNode('adapter')
                            ->defaultNull()
                            ->info('The service to use to create Turbo Streams')
                        ->end()
                        ->arrayNode('options')
                            ->info('Key/value options passed to the adapter to create Turbo Streams')
                            ->normalizeKeys(false)
                            ->variablePrototype()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
