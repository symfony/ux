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
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentRenderer;
use Symfony\UX\TwigComponent\ComponentRendererInterface;
use Symfony\UX\TwigComponent\ComponentStack;
use Symfony\UX\TwigComponent\DependencyInjection\Compiler\TwigComponentPass;
use Symfony\UX\TwigComponent\EventListener\DataModelPropsSubscriber;
use Symfony\UX\TwigComponent\Twig\ComponentExtension;

/**
 * @author Kevin Bond <kevinbond@gmail.com>

 *
 * @internal
 */
final class TwigComponentExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        if (!isset($container->getParameter('kernel.bundles')['TwigBundle'])) {
            throw new LogicException('The TwigBundle is not registered in your application. Try running "composer require symfony/twig-bundle".');
        }

        $container->registerAttributeForAutoconfiguration(
            AsTwigComponent::class,
            static function (ChildDefinition $definition, AsTwigComponent $attribute) {
                $definition->addTag('twig.component', array_filter($attribute->serviceConfig()));
            }
        );

        $container->register('ux.twig_component.component_factory', ComponentFactory::class)
            ->setArguments([
                new ServiceLocatorArgument(new TaggedIteratorArgument('twig.component', 'key', null, true)),
                new Reference('property_accessor'),
                new Reference('event_dispatcher'),
                class_exists(AbstractArgument::class) ? new AbstractArgument(sprintf('Added in %s.', TwigComponentPass::class)) : [],
            ])
        ;

        $container->register('ux.twig_component.component_stack', ComponentStack::class);

        $container->register('ux.twig_component.component_renderer', ComponentRenderer::class)
            ->setArguments([
                new Reference('twig'),
                new Reference('event_dispatcher'),
                new Reference('ux.twig_component.component_factory'),
                new Reference('property_accessor'),
                new Reference('ux.twig_component.component_stack'),
            ])
        ;

        $container->setAlias(ComponentRendererInterface::class, 'ux.twig_component.component_renderer');

        $container->register('ux.twig_component.twig.component_extension', ComponentExtension::class)
            ->addTag('twig.extension')
            ->addTag('container.service_subscriber', ['key' => ComponentRenderer::class, 'id' => 'ux.twig_component.component_renderer'])
            ->addTag('container.service_subscriber', ['key' => ComponentFactory::class, 'id' => 'ux.twig_component.component_factory'])
        ;

        $container->register('ux.twig_component.event_listener.data_model_props_subscriber', DataModelPropsSubscriber::class)
            ->addTag('kernel.event_subscriber')
            ->setArguments([
                new Reference('ux.twig_component.component_stack'),
                new Reference('property_accessor'),
            ])
        ;
    }
}
