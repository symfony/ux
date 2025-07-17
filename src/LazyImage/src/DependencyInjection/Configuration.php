<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LazyImage\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

trigger_deprecation('symfony/ux-lazy-image', '2.27.0', 'The package is deprecated and will be removed in 3.0.');

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('ux_lazy_image');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->scalarNode('cache')->end()
                ->scalarNode('fetch_image_content')->defaultNull()->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
