<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
                ->booleanNode('asset_mapper_mode')
                    ->info(<<<'EOF'
                        If set to 'true', translations will be dumped as separated modules for each domain.
                        This allows loading only the desired domains when using AssetMapper.
                        EOF)
                    ->defaultValue(false)
                    ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
