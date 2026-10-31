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
                ->arrayNode('broadcast')
                    ->canBeDisabled()
                    ->children()
                        ->arrayNode('entity_template_prefixes')
                            ->fixXmlConfig('entity_template_prefix')
                            ->defaultValue(['App\Entity\\' => 'broadcast/'])
                            ->scalarPrototype()->end()
                        ->end()
                        ->arrayNode('doctrine_orm')
                            ->info('Enable the Doctrine ORM integration')
                            ->{class_exists(DoctrineBundle::class) && interface_exists(EntityManagerInterface::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('default_transport')->defaultValue('default')->end()
                ->arrayNode('mercure')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('ignore_cookie_domain_errors')
                            ->info('Render nothing and log a warning, instead of failing, when the authorization cookie of a private stream source cannot be created because the page is served on another domain than the hub')
                            ->defaultFalse()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
