<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\BootstrapTable\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\UX\BootstrapTable\Builder\TableBuilder;
use Symfony\UX\BootstrapTable\Builder\TableBuilderInterface;
use Symfony\UX\BootstrapTable\Twig\BootstrapTableExtension as TwigExtension;
use Symfony\WebpackEncoreBundle\Twig\StimulusTwigExtension;
use Twig\Environment;

/**
 * @author Mathéo Daninos <mathéo.daninos@gmail.com>
 */
class BootstrapTableExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $container
            ->setDefinition('bootstrapTable.builder', new Definition(TableBuilder::class))
            ->setPublic(false)
        ;

        $container
            ->setAlias(TableBuilderInterface::class, 'bootstrapTable.builder')
            ->setPublic(false)
        ;

        if (class_exists(Environment::class) && class_exists(StimulusTwigExtension::class)) {
            $container
                ->setDefinition('bootstrapTable.twig_extension', new Definition(TwigExtension::class))
                ->addArgument(new Reference('webpack_encore.twig_stimulus_extension'))
                ->addTag('twig.extension')
                ->setPublic(false)
            ;
        }
    }
}
