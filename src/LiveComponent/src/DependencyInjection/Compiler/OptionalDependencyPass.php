<?php

namespace Symfony\UX\LiveComponent\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\UX\LiveComponent\Hydration\DoctrineEntityHydrationExtension;
use Symfony\UX\LiveComponent\LiveComponentBundle;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 *
 * @internal
 */
final class OptionalDependencyPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition('doctrine')) {
            $container->register('ux.live_component.doctrine_entity_hydration_extension', DoctrineEntityHydrationExtension::class)
                ->setArguments([new IteratorArgument([new Reference('doctrine')])]) // TODO: add support for multiple entity managers
                ->addTag(LiveComponentBundle::HYDRATION_EXTENSION_TAG)
            ;
        }
    }
}
