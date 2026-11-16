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
 * @internal
 */
final class LiveComponentPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $liveComponentList = [];
        $hookMethods = [];
        foreach ($container->findTaggedServiceIds('twig.component') as $id => $tags) {
            if (!($tags[0]['live'] ?? false)) {
                continue;
            }

            $className = $container->findDefinition($id)->getClass();
            $liveComponentList[$className] = $className;
            $hookMethods[$className] ??= [
                'pre_dehydrate' => array_column(AsLiveComponent::preDehydrateMethods($className), 'name'),
                'post_hydrate' => array_column(AsLiveComponent::postHydrateMethods($className), 'name'),
                'pre_re_render' => array_column(AsLiveComponent::preReRenderMethods($className), 'name'),
                'actions' => AsLiveComponent::liveActions($className),
                'listeners' => AsLiveComponent::liveListeners($className),
            ];
        }

        $container->findDefinition('ux.live_component.metadata_factory')->setArgument(4, $hookMethods);

        $debugCommandDefinition = $container->findDefinition('ux.live_component.command.debug');
        $debugCommandDefinition->setArgument(1, $liveComponentList);

        $container->findDefinition('ux.live_component.metadata_cache_warmer')->setArgument(1, array_values($liveComponentList));
    }
}
