<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\UX\LiveComponent\Hydration\DoctrineArrayCollectionHydrationTrait;
use Symfony\UX\LiveComponent\Hydration\DoctrineEntityHydrationTrait;
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
            $container->register('ux.live_component.doctrine_entity_hydration_extension', DoctrineEntityHydrationTrait::class)
                ->setArguments([new IteratorArgument([new Reference('doctrine')])]) // TODO: add support for multiple entity managers
                ->addTag(LiveComponentBundle::HYDRATION_EXTENSION_TAG)
            ;

            $container->register('ux.live_component.doctrine_array_collection_hydration_extension', DoctrineArrayCollectionHydrationTrait::class)
                ->setArguments([new IteratorArgument([new Reference('doctrine')])]) // TODO: add support for multiple entity managers
                ->addTag(LiveComponentBundle::HYDRATION_EXTENSION_TAG)
            ;
        }
    }
}
