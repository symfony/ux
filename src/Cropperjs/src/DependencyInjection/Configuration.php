<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Cropperjs\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @internal
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('cropperjs');

        $treeBuilder->getRootNode()
            ->children()
                ->enumNode('driver')
                    ->info('The Intervention Image driver used for server-side cropping.')
                    ->values(['gd', 'imagick', 'vips'])
                    ->defaultValue('gd')
                ->end()
                ->scalarNode('driver_service')
                    ->info('Service id of a custom Intervention\Image\Interfaces\DriverInterface. When set, it takes precedence over "driver".')
                    ->defaultNull()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
