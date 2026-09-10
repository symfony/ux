<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Disclose\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 *
 * @internal
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('disclose');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('rate_limiter')
                    ->info('Names of the framework rate limiters combined for every disclosure. A request is accepted only when every limiter accepts it (for example a burst window plus a daily quota).')
                    ->beforeNormalization()
                        ->ifString()
                        ->then(static fn(string $value): array => [$value])
                    ->end()
                    ->defaultValue(['ux_disclose'])
                    ->scalarPrototype()->end()
                ->end()
                ->scalarNode('rate_limiter_subject_factory')
                    ->info('Service id computing the rate-limit subject, to key the limiter differently than user-then-IP.')
                    ->defaultNull()
                ->end()
                ->scalarNode('logger')
                    ->info('PSR-3 logger service id used for the audit trail.')
                    ->defaultValue('logger')
                ->end()
                ->integerNode('ttl')
                    ->info('Lifetime in seconds of a signed disclose URL. A signed reference expires after this duration, so a URL captured from a page, a proxy log or a browser history cannot be replayed indefinitely. Defaults to one hour.')
                    ->min(1)
                    ->defaultValue(3600)
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
