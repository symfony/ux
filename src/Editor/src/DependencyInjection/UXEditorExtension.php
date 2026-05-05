<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class UXEditorExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new PhpFileLoader($container, new FileLocator(\dirname(__DIR__, 2).'/config'));
        $loader->load('services.php');

        $container->setParameter('ux_editor.html.sanitize_required', $config['html']['sanitize_required']);
        $container->setParameter('ux_editor.upload.default_profile', $config['upload']['default_profile']);
        $container->setParameter('ux_editor.upload.ttl_seconds', $config['upload']['ttl_seconds']);
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ?\Symfony\Component\Config\Definition\ConfigurationInterface
    {
        return new Configuration();
    }

    public function getAlias(): string
    {
        return 'ux_editor';
    }
}
