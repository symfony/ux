<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\UX\DataTable\Builder\TableBuilder;
use Symfony\UX\DataTable\Builder\TableBuilderInterface;
use Symfony\UX\DataTable\Twig\DataTableExtension as TwigExtension;
use Symfony\WebpackEncoreBundle\Twig\StimulusTwigExtension;
use Twig\Environment;

/**
 * @author Mathéo Daninos <mathéo.daninos@gmail.com>
 */
class DataTableExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $container
            ->setDefinition('datatable.builder', new Definition(TableBuilder::class))
            ->setPublic(false)
        ;

        $container
            ->setAlias(TableBuilderInterface::class, 'datatable.builder')
            ->setPublic(false)
        ;

        if (class_exists(Environment::class) && class_exists(StimulusTwigExtension::class)) {
            $container
                ->setDefinition('datatable.twig_extension', new Definition(TwigExtension::class))
                ->addArgument(new Reference('webpack_encore.twig_stimulus_extension'))
                ->addTag('twig.extension')
                ->setPublic(false)
            ;
        }
    }
}
