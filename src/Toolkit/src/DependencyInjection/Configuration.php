<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Jean-François Lépine
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('ux_toolkit');

        $treeBuilder->getRootNode()
            ->children()
                ->stringNode('theme')
                    ->defaultValue('default')
                ->end()
                ->stringNode('prefix')
                    ->defaultValue(null)
                ->end()
            ->end();

        return $treeBuilder;
    }
}
