<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\DependencyInjection;

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\ComponentValidator;
use Symfony\UX\LiveComponent\ComponentValidatorInterface;
use Symfony\UX\LiveComponent\EventListener\AddLiveAttributesSubscriber;
use Symfony\UX\LiveComponent\EventListener\LiveComponentSubscriber;
use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Symfony\UX\LiveComponent\Twig\LiveComponentExtension as LiveComponentTwigExtension;
use Symfony\UX\LiveComponent\Twig\LiveComponentRuntime;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentRenderer;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 */
final class LiveComponentExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->registerAttributeForAutoconfiguration(
            AsLiveComponent::class,
            function (ChildDefinition $definition, AsLiveComponent $attribute) {
                $definition
                    ->addTag('twig.component', array_filter([
                        'key' => $attribute->name,
                        'template' => $attribute->template,
                        'default_action' => $attribute->defaultAction,
                        'live' => true,
                    ]))
                    ->addTag('controller.service_arguments')
                ;
            }
        );

        $container->register('ux.live_component.component_hydrator', LiveComponentHydrator::class)
            ->setArguments([
                new Reference('serializer'),
                new Reference('property_accessor'),
                '%kernel.secret%',
            ])
        ;

        $container->register('ux.live_component.event_subscriber', LiveComponentSubscriber::class)
            ->addTag('kernel.event_subscriber')
            ->addTag('container.service_subscriber', ['key' => ComponentFactory::class, 'id' => 'ux.twig_component.component_factory'])
            ->addTag('container.service_subscriber', ['key' => ComponentRenderer::class, 'id' => 'ux.twig_component.component_renderer'])
            ->addTag('container.service_subscriber', ['key' => LiveComponentHydrator::class, 'id' => 'ux.live_component.component_hydrator'])
            ->addTag('container.service_subscriber') // csrf
        ;

        $container->register('ux.live_component.twig.component_extension', LiveComponentTwigExtension::class)
            ->addTag('twig.extension')
        ;

        $container->register('ux.live_component.twig.component_runtime', LiveComponentRuntime::class)
            ->setArguments([
                new Reference('ux.live_component.component_hydrator'),
                new Reference('ux.twig_component.component_factory'),
                new Reference('router'),
            ])
            ->addTag('twig.runtime')
        ;

        $container->register(ComponentValidator::class)
            ->addTag('container.service_subscriber', ['key' => 'validator', 'id' => 'validator'])
        ;

        $container->register('ux.live_component.add_attributes_subscriber', AddLiveAttributesSubscriber::class)
            ->addTag('kernel.event_subscriber')
            ->addTag('container.service_subscriber', ['key' => LiveComponentHydrator::class, 'id' => 'ux.live_component.component_hydrator'])
            ->addTag('container.service_subscriber') // csrf, twig & router
        ;

        $container->setAlias(ComponentValidatorInterface::class, ComponentValidator::class);
    }
}
