<?php

/*
 * This file is part of the Mercure Component project.
 *
 * (c) Kévin Dunglas <kevin@dunglas.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\UX\Turbo\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Turbo configuration structure.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('turbo');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->arrayNode('mercure')
                    ->children()
                        ->scalarNode('subscribe_url')->info('URL of the Mercure Hub to use for subscriptions')->example('https://example.com/.well-known/mercure')->end()
                    ->end()
                ->end()
                ->arrayNode('broadcast')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('entity_namespace')->info('Prefix to strip when looking for broadcast templates')->defaultValue('App\\Entity\\')->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
