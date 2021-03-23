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

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\MercureBundle\MercureBundle;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Turbo configuration structure.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 *
 * @experimental
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('turbo');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->arrayNode('broadcast')
                    ->{\PHP_VERSION_ID >= 80000 ? 'canBeDisabled' : 'canBeEnabled'}()
                    ->children()
                        ->scalarNode('entity_namespace')->info('Prefix to strip when looking for broadcast templates')->defaultValue('App\\Entity\\')->end()
                        ->arrayNode('doctrine_orm')
                            ->info('Enable the Doctrine ORM integration')
                            ->{class_exists(DoctrineBundle::class) && interface_exists(EntityManagerInterface::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('default_transport')->defaultValue('default')->end()
                ->arrayNode('mercure')
                    ->{class_exists(MercureBundle::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                    ->info('If the Mercure hubs to use as transports aren\'t configured explicitly, a transport named "default" using the default Mercure hub will be automatically created.')
                    ->children()
                        ->arrayNode('hubs')->scalarPrototype()->info('The name of the Mercure hubs  (configured in MercureBundle) to use as transports')->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
