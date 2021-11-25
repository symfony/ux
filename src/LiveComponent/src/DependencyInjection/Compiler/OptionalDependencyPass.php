<?php

namespace Symfony\UX\LiveComponent\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\UX\LiveComponent\Hydrator\DoctrineEntityPropertyHydrator;
use Symfony\UX\LiveComponent\Hydrator\NormalizerBridgePropertyHydrator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class OptionalDependencyPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition('doctrine')) {
            $container->register('ux.live_component.doctrine_entity_property_hydrator', DoctrineEntityPropertyHydrator::class)
                ->setArguments([[new Reference('doctrine')]])
                ->addTag('twig.component.property_hydrator', ['priority' => -100])
            ;
        }

        if ($container->hasDefinition('serializer')) {
            $container->register('ux.live_component.serializer_property_hydrator', NormalizerBridgePropertyHydrator::class)
                ->setArguments([new Reference('serializer')])
                ->addTag('twig.component.property_hydrator', ['priority' => -200])
            ;
        }
    }
}
