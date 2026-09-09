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
use Symfony\UX\Toolkit\Installer\ComponentDirectory;

/**
 * @author Jean-François Lépine
 * @author Hugo Alliaume <hugo@alliau.me>
 */
class UXToolkitBundle extends AbstractBundle
{
    protected string $extensionAlias = 'ux_toolkit';

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('component_dir')
                    ->info('The directory, relative to the installation destination, where the Twig components of a recipe are installed.')
                    ->defaultValue(ComponentDirectory::DEFAULT_PATH)
                    ->example('templates/components/ui')
                    ->cannotBeEmpty()
                ->end()
            ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->setParameter('ux_toolkit.component_dir', $config['component_dir']);

        $container->import('../config/services.php');
    }
}
