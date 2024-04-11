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
use Symfony\UX\LiveComponent\Command\LiveComponentDebugCommand;
use Symfony\UX\LiveComponent\DataCollector\LiveComponentDataCollector;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class DebugLiveComponentPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('ux.twig_component.data_collector')) {
            return;
        }

        $container->getDefinition('ux.twig_component.data_collector')
            ->setClass(LiveComponentDataCollector::class)
            ->clearTag('data_collector')
            ->addTag('data_collector', [
                'template' => '@LiveComponent/Collector/live_component.html.twig',
                'id' => 'live_component',
                'priority' => 256,
            ]);

        $container->getDefinition('ux.twig_component.command.debug')
            ->setClass(LiveComponentDebugCommand::class)
            ->addTag('console.command', ['command' => 'debug:live-component'])
        ;
    }
}
