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

/**
 * @internal
 */
final class LiveComponentPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $liveComponentList = [];
        foreach ($container->findTaggedServiceIds('twig.component') as $id => $tags) {
            if (!($tags[0]['live'] ?? false)) {
                continue;
            }

            $className = $container->findDefinition($id)->getClass();
            $liveComponentList[$className] = $className;
        }

        $debugCommandDefinition = $container->findDefinition('ux.live_component.command.debug');
        $debugCommandDefinition->setArgument(1, $liveComponentList);
    }
}
