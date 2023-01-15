<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Core\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\UX\DataTable\Core\Twig\DataTableExtension as TwigExtension;

/**
 * @author Mathéo Daninos <mathéo.daninos@gmail.com>
 */
class DataTableExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $container
            ->setDefinition('data_table.twig_extension', new Definition(TwigExtension::class))
            ->addArgument(new Reference('service.container'))
            ->addTag('twig.extension')
            ->setPublic(false)
        ;
    }
}
