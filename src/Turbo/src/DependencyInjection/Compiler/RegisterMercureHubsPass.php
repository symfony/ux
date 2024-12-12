<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\UX\Turbo\Bridge\Mercure\Broadcaster;
use Symfony\UX\Turbo\Bridge\Mercure\TurboStreamListenRenderer;

/**
 * This compiler pass ensures that TurboStreamListenRenderer
 * and Broadcast are registered per Mercure hub.
 *
 * @author Pierre Ambroise<pierre27.ambroise@gmail.com>
 */
final class RegisterMercureHubsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('mercure.hub') as $hubId => $tags) {
            $name = str_replace('mercure.hub.', '', $hubId);

            $container->register("turbo.mercure.$name.renderer", TurboStreamListenRenderer::class)
                ->addArgument(new Reference($hubId))
                ->addArgument(new Reference('turbo.mercure.stimulus_helper'))
                ->addArgument(new Reference('turbo.id_accessor'))
                ->addArgument(new Reference('twig'))
                ->addTag('turbo.renderer.stream_listen', ['transport' => $name]);

            foreach ($tags as $tag) {
                if (isset($tag['default']) && $tag['default'] && 'default' !== $name) {
                    $container->getDefinition("turbo.mercure.$name.renderer")
                        ->addTag('turbo.renderer.stream_listen', ['transport' => 'default']);
                }
            }

            $container->register("turbo.mercure.$name.broadcaster", Broadcaster::class)
                ->addArgument($name)
                ->addArgument(new Reference($hubId))
                ->addTag('turbo.broadcaster');
        }
    }
}
