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
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

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
                ->arrayNode('domains')
                    ->info('List of domains to include/exclude from the generated translations. Prefix with a `!` to exclude a domain.')
                    ->children()
                        ->scalarNode('type')
                            ->validate()
                                ->ifNotInArray(['inclusive', 'exclusive'])
                                ->thenInvalid('The type of domains has to be inclusive or exclusive')
                            ->end()
                        ->end()
                        ->arrayNode('elements')
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
                    ->canBeUnset()
                    ->beforeNormalization()
                        ->ifString()
                        ->then(fn ($v) => ['elements' => [$v]])
                    ->end()
                    ->beforeNormalization()
                        ->ifTrue(function ($v) { return \is_array($v) && is_numeric(key($v)); })
                        ->then(function ($v) { return ['elements' => $v]; })
                    ->end()
                    ->validate()
                        ->always(function ($v) {
                            $isExclusive = null;
                            $elements = [];
                            if (isset($v['type'])) {
                                $isExclusive = 'exclusive' === $v['type'];
                            }
                            foreach ($v['elements'] as $domain) {
                                if (str_starts_with($domain, '!')) {
                                    if (false === $isExclusive) {
                                        throw new InvalidConfigurationException('You cannot mix included and excluded domains.');
                                    }
                                    $isExclusive = true;
                                    $elements[] = substr($domain, 1);
                                } else {
                                    if (true === $isExclusive) {
                                        throw new InvalidConfigurationException('You cannot mix included and excluded domains.');
                                    }
                                    $isExclusive = false;
                                    $elements[] = $domain;
                                }
                            }

                            if (!\count($elements)) {
                                return null;
                            }

                            return ['type' => $isExclusive ? 'exclusive' : 'inclusive', 'elements' => array_unique($elements)];
                        })
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
