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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 */
final class LiveComponentPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $componentServiceMap = [];

        foreach (array_keys($container->findTaggedServiceIds('twig.component')) as $id) {
            try {
                $attribute = AsLiveComponent::forClass($container->findDefinition($id)->getClass());
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            $componentServiceMap[$attribute->getName()] = $id;
        }

        $container->findDefinition('ux.live_component.event_subscriber')->setArgument(0, $componentServiceMap);
    }
}
