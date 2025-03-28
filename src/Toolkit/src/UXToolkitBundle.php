<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @author Jean-François Lépine
 * @author Hugo Alliaume <hugo@alliau.me>
 */
class UXToolkitBundle extends AbstractBundle
{
    protected string $extensionAlias = 'ux_toolkit';

    public function configure(DefinitionConfigurator $definition): void
    {
        $rootNode = $definition->rootNode();
        $rootNode
            ->children()
                ->scalarNode('theme')
                    ->defaultValue('default')
                ->end()
                ->scalarNode('prefix')
                    ->defaultNull()
                ->end()
            ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->parameters()
            ->set('ux_toolkit.theme', $config['theme'])
            ->set('ux_toolkit.prefix', $config['prefix'])
        ;

        $container->import('../config/services.php');
    }
}
