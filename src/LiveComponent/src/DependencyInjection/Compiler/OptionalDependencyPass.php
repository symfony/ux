<?php

namespace Symfony\UX\LiveComponent\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\UX\LiveComponent\Normalizer\DoctrineObjectNormalizer;

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
            $container->register('ux.live_component.doctrine_object_normalizer', DoctrineObjectNormalizer::class)
                ->setArguments([new IteratorArgument([new Reference('doctrine')])]) // todo add other object managers (mongo)
                ->addTag('serializer.normalizer', ['priority' => -100])
            ;
        }
    }
}
