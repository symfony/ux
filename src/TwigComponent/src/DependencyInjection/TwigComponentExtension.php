<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\DependencyInjection;

use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentInterface;
use Symfony\UX\TwigComponent\ComponentRenderer;
use Symfony\UX\TwigComponent\Twig\ComponentExtension;
use Symfony\UX\TwigComponent\Twig\ComponentRuntime;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 */
final class TwigComponentExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(ComponentInterface::class)
            ->addTag('twig.component')
        ;

        $container->register(ComponentFactory::class)
            ->setArguments([
                new ServiceLocatorArgument(new TaggedIteratorArgument('twig.component', null, 'getComponentName')),
                new Reference('property_accessor'),
            ])
        ;

        $container->register(ComponentRenderer::class)
            ->setArguments([
                new Reference('twig'),
            ])
        ;

        $container->register(ComponentExtension::class)
            ->addTag('twig.extension')
        ;

        $container->register(ComponentRuntime::class)
            ->setArguments([
                new Reference(ComponentFactory::class),
                new Reference(ComponentRenderer::class),
            ])
            ->addTag('twig.runtime')
        ;
    }
}
