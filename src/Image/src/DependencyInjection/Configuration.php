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
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('ux_image');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('provider')
                    ->info('Image provider to use')
                    ->isRequired()
                ->end()
                ->scalarNode('missing_image_placeholder')
                    ->info('Path to the image shown when source image is missing')
                    ->isRequired()
                ->end()
                ->arrayNode('breakpoints')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('name')
                    ->prototype('integer')
                        ->min(1)
                    ->end()
                ->end()
                ->arrayNode('defaults')
                    ->children()
                        ->enumNode('format')
                            ->values(['webp', 'jpg', 'png', 'avif'])
                        ->end()
                        ->integerNode('quality')
                            ->min(1)
                            ->max(100)
                        ->end()
                        ->enumNode('loading')
                            ->values(['lazy', 'eager'])
                        ->end()
                        ->enumNode('fetchpriority')
                            ->values(['high', 'low', 'auto'])
                        ->end()
                        ->enumNode('fit')
                            ->values(['cover', 'contain', 'fill', 'inside', 'outside'])
                        ->end()
                        ->enumNode('placeholder')
                            ->values(['none', 'blur', 'dominant'])
                        ->end()
                        ->scalarNode('fallback')
                            ->defaultValue('lg')
                            ->info('Default breakpoint to use for fallback image')
                        ->end()
                        ->enumNode('fallback_format')
                            ->values(['auto', 'jpg', 'png', 'empty'])
                            ->defaultValue('auto')
                            ->info('Default format to use for fallback image')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('providers')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->useAttributeAsKey('name')
                        ->variablePrototype()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('presets')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('width')
                            ->end()
                            ->integerNode('height')
                                ->min(1)
                            ->end()
                            ->scalarNode('ratio')->end()
                            ->enumNode('fit')
                                ->values(['cover', 'contain', 'fill', 'inside', 'outside'])
                            ->end()
                            ->enumNode('loading')
                                ->values(['lazy', 'eager'])
                            ->end()
                            ->enumNode('fetchpriority')
                                ->values(['high', 'low', 'auto'])
                            ->end()
                            ->enumNode('placeholder')
                                ->values(['none', 'blur', 'dominant'])
                            ->end()
                            ->integerNode('quality')
                                ->min(1)
                                ->max(100)
                            ->end()
                            ->booleanNode('preload')
                                ->defaultFalse()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
