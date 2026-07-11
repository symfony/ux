<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tb = new TreeBuilder('ux_editor');
        $tb->getRootNode()
            ->children()
                ->arrayNode('html')->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('sanitize_required')->defaultTrue()->end()
                    ->end()
                ->end()
                ->arrayNode('upload')->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('default_profile')->defaultValue('default')->end()
                        ->integerNode('ttl_seconds')->defaultValue(3600)->end()
                    ->end()
                ->end()
            ->end();

        return $tb;
    }
}
