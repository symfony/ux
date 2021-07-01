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

use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 */
final class TwigComponentPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $componentMap = [];

        foreach (array_keys($container->findTaggedServiceIds('twig.component')) as $id) {
            $componentDefinition = $container->findDefinition($id);

            try {
                $attribute = AsTwigComponent::forClass($componentDefinition->getClass());
            } catch (\InvalidArgumentException $e) {
                throw new LogicException(sprintf('Service "%s" is tagged as a "twig.component" but does not have a "%s" class attribute.', $id, AsTwigComponent::class), 0, $e);
            }

            $componentMap[$attribute->getName()] = new Reference($id);

            // component services must not be shared
            $componentDefinition->setShared(false);
        }

        $container->findDefinition('ux.twig_component.component_factory')
            ->setArgument(0, new ServiceLocatorArgument($componentMap))
        ;
    }
}
