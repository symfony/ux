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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Aleksey Razbakov <aleksey@razbakov.com>
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('ux_image');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('default_provider')
                    ->defaultValue('passthrough')
                    ->info('The default provider to use when none is specified.')
                ->end()
                ->arrayNode('providers')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('pattern')->end()
                            ->scalarNode('assets_path')->end()
                            ->arrayNode('config')
                                ->useAttributeAsKey('key')
                                ->scalarPrototype()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('breakpoints')
                    ->useAttributeAsKey('name')
                    ->integerPrototype()->end()
                    ->defaultValue([
                        'sm' => 640,
                        'md' => 768,
                        'lg' => 1024,
                        'xl' => 1280,
                        '2xl' => 1536,
                    ])
                ->end()
                ->arrayNode('defaults')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('format')->defaultValue('webp')->end()
                        ->integerNode('quality')->defaultValue(80)->end()
                        ->scalarNode('loading')->defaultValue('lazy')->end()
                        ->scalarNode('fit')->defaultValue('cover')->end()
                        ->scalarNode('focal')->defaultValue('center')->end()
                        ->scalarNode('fallback')->defaultValue('lg')->end()
                        ->scalarNode('fallback_format')->defaultValue('auto')->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
