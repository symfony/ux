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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author Jean-François Lépine
 */
class ToolkitExtension extends Extension
{
    public function getAlias(): string
    {
        return 'ux_toolkit';
    }

    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration();
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        // Expose the prefix and theme configured as parameter (for the moment). It will be injected to
        // the service responsible for rendering the components.
        $container->setParameter('ux_toolkit.theme', $config['theme']);
        $container->setParameter('ux_toolkit.prefix', $config['prefix']);
    }
}
