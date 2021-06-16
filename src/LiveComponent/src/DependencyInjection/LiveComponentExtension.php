<?php

namespace Symfony\UX\LiveComponent\DependencyInjection;

use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\UX\LiveComponent\ComponentValidator;
use Symfony\UX\LiveComponent\ComponentValidatorInterface;
use Symfony\UX\LiveComponent\EventListener\LiveComponentSubscriber;
use Symfony\UX\LiveComponent\Hydrator\DoctrineEntityPropertyHydrator;
use Symfony\UX\LiveComponent\Hydrator\NormalizerBridgePropertyHydrator;
use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Symfony\UX\LiveComponent\LiveComponentInterface;
use Symfony\UX\LiveComponent\PropertyHydratorInterface;
use Symfony\UX\LiveComponent\Twig\LiveComponentExtension as LiveComponentTwigExtension;
use Symfony\UX\LiveComponent\Twig\LiveComponentRuntime;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LiveComponentExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(LiveComponentInterface::class)
            ->addTag('controller.service_arguments')
        ;

        $container->registerForAutoconfiguration(PropertyHydratorInterface::class)
            ->addTag('twig.component.property_hydrator')
        ;

        $container->register(DoctrineEntityPropertyHydrator::class)
            ->setArguments([[new Reference('doctrine')]]) // TODO: additional object managers
            ->addTag('twig.component.property_hydrator', ['priority' => -200])
        ;

        $container->register('twig.component.datetime_property_hydrator', NormalizerBridgePropertyHydrator::class)
            ->setArguments([new Reference('serializer.normalizer.datetime')])
            ->addTag('twig.component.property_hydrator', ['priority' => -100])
        ;

        $container->register(LiveComponentHydrator::class)
            ->setArguments([
                new TaggedIteratorArgument('twig.component.property_hydrator'),
                new Reference('property_accessor'),
                new Reference('annotation_reader'),
                '%kernel.secret%',
            ])
        ;

        $container->register(LiveComponentSubscriber::class)
            ->addTag('kernel.event_subscriber')
            ->addTag('container.service_subscriber')
        ;

        $container->register(LiveComponentTwigExtension::class)
            ->addTag('twig.extension')
        ;

        $container->register(LiveComponentRuntime::class)
            ->setArguments([
                new Reference(LiveComponentHydrator::class),
                new Reference(UrlGeneratorInterface::class),
                new Reference(CsrfTokenManagerInterface::class, ContainerBuilder::NULL_ON_INVALID_REFERENCE),
            ])
            ->addTag('twig.runtime')
        ;

        $container->register(ComponentValidator::class)
            ->addTag('container.service_subscriber', ['key' => 'validator', 'id' => 'validator'])
            ->addTag('container.service_subscriber', ['key' => 'property_accessor', 'id' => 'property_accessor'])
        ;

        $container->setAlias(ComponentValidatorInterface::class, ComponentValidator::class);
    }
}
