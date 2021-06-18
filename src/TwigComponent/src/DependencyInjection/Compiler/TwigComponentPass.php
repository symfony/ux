<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\UX\TwigComponent\ComponentFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 */
final class TwigComponentPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $serviceIdMap = [];

        foreach (array_keys($container->findTaggedServiceIds('twig.component')) as $serviceId) {
            $definition = $container->getDefinition($serviceId);

            // make all component services non-shared
            $definition->setShared(false);

            $name = $definition->getClass()::getComponentName();

            // ensure component not already defined
            if (\array_key_exists($name, $serviceIdMap)) {
                throw new LogicException(sprintf('Component "%s" is already registered as "%s", components cannot be registered more than once.', $definition->getClass(), $serviceIdMap[$name]));
            }

            // add to service id map for ComponentFactory
            $serviceIdMap[$name] = $serviceId;
        }

        $container->getDefinition(ComponentFactory::class)->setArgument(2, $serviceIdMap);
    }
}
