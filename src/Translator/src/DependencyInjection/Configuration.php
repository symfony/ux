<?php

namespace Symfony\UX\Translator\DependencyInjection;

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
        $treeBuilder = new TreeBuilder('ux_translator');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->scalarNode('dump_directory')->defaultValue('%kernel.project_dir%/var/translations')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
