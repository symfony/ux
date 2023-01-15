<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\BootstrapTable\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\UX\DataTable\BootstrapTable\BootstrapTableBuilder;
use Symfony\UX\DataTable\BootstrapTable\BootstrapTableRenderer;
use Symfony\UX\DataTable\Core\Twig\DataTableExtension as TwigExtension;

/**
 * @author Mathéo Daninos <mathéo.daninos@gmail.com>
 */
class BootstrapTableExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $container
            ->setDefinition('bootstrapTable.builder', new Definition(BootstrapTableBuilder::class))
            ->setPublic(false)
        ;

        $container
            ->setAlias(BootstrapTableBuilder::class, 'bootstrapTable.builder')
            ->setPublic(false)
        ;

        $container
            ->setDefinition('data_table.twig_extension', new Definition(TwigExtension::class))
            ->addArgument(new Reference('service_container'))
            ->addTag('twig.extension')
            ->setPublic(false)
        ;

        $container
            ->setDefinition('bootstrap_table.renderer', new Definition(BootstrapTableRenderer::class))
            ->addArgument(new Reference('webpack_encore.twig_stimulus_extension'))
            ->setPublic(true)
        ;

        $container
            ->setAlias(BootstrapTableRenderer::class, 'bootstrap_table.renderer')
            ->setPublic(true)
        ;
    }
}
