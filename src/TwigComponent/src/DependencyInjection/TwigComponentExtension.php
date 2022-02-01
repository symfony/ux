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

use Symfony\Component\DependencyInjection\Argument\AbstractArgument;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentRenderer;
use Symfony\UX\TwigComponent\DependencyInjection\Compiler\TwigComponentPass;
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
        $container->registerAttributeForAutoconfiguration(
            AsTwigComponent::class,
            static function (ChildDefinition $definition, AsTwigComponent $attribute) {
                $definition->addTag('twig.component', array_filter([
                    'key' => $attribute->name,
                    'template' => $attribute->template,
                ]));
            }
        );

        $container->register('ux.twig_component.component_factory', ComponentFactory::class)
            ->setArguments([
                new ServiceLocatorArgument(new TaggedIteratorArgument('twig.component', 'key', null, true)),
                new Reference('property_accessor'),
                class_exists(AbstractArgument::class) ? new AbstractArgument(sprintf('Added in %s.', TwigComponentPass::class)) : [],
            ])
        ;

        $container->register('ux.twig_component.component_renderer', ComponentRenderer::class)
            ->setArguments([
                new Reference('twig'),
                new Reference('event_dispatcher'),
                new Reference('ux.twig_component.component_factory'),
                new Reference('property_accessor'),
            ])
        ;

        $container->register('ux.twig_component.twig.component_extension', ComponentExtension::class)
            ->addTag('twig.extension')
        ;

        $container->register('ux.twig_component.twig.component_runtime', ComponentRuntime::class)
            ->setArguments([
                new Reference('ux.twig_component.component_factory'),
                new Reference('ux.twig_component.component_renderer'),
            ])
            ->addTag('twig.runtime')
        ;
    }
}
