<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Unit\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\UX\LiveComponent\Command\LiveComponentDebugCommand;
use Symfony\UX\LiveComponent\DependencyInjection\Compiler\LiveComponentPass;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadataCacheWarmer;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadataFactory;
use Symfony\UX\LiveComponent\Tests\Fixtures\Component\Component2;
use Symfony\UX\LiveComponent\Tests\Fixtures\Component\Component3;

final class LiveComponentPassTest extends TestCase
{
    public function testGivesTheLiveComponentClassesToTheMetadataCacheWarmer(): void
    {
        $container = new ContainerBuilder();
        $container->register('ux.live_component.command.debug', LiveComponentDebugCommand::class);
        $container->register('ux.live_component.metadata_factory', LiveComponentMetadataFactory::class);
        $container->register('ux.live_component.metadata_cache_warmer', LiveComponentMetadataCacheWarmer::class);
        $container->register('live', Component2::class)->addTag('twig.component', ['key' => 'live', 'live' => true]);
        $container->register('not_live', Component3::class)->addTag('twig.component', ['key' => 'not_live']);

        new LiveComponentPass()->process($container);

        $classes = $container->getDefinition('ux.live_component.metadata_cache_warmer')->getArgument(1);
        $this->assertSame([Component2::class], $classes);
    }

    public function testListsTheHookMethodsOfEachLiveComponentClass(): void
    {
        $container = new ContainerBuilder();
        $container->register('ux.live_component.command.debug', LiveComponentDebugCommand::class);
        $container->register('ux.live_component.metadata_factory', LiveComponentMetadataFactory::class);
        $container->register('ux.live_component.metadata_cache_warmer', LiveComponentMetadataCacheWarmer::class);
        $container->register('live', Component2::class)->addTag('twig.component', ['key' => 'live', 'live' => true]);
        $container->register('not_live', Component3::class)->addTag('twig.component', ['key' => 'not_live']);

        new LiveComponentPass()->process($container);

        $hookMethods = $container->getDefinition('ux.live_component.metadata_factory')->getArgument(4);
        $this->assertSame(
            [
                Component2::class => [
                    'pre_dehydrate' => ['preDehydrateMethod'],
                    'post_hydrate' => ['postHydrateMethod'],
                    'pre_re_render' => ['preReRenderMethod'],
                    'actions' => ['increase', 'redirect', 'increaseEvent1', 'increaseEvent2'],
                    'listeners' => [
                        ['action' => 'increaseEvent1', 'event' => 'triggerIncrease'],
                        ['action' => 'increaseEvent2', 'event' => 'triggerIncrease'],
                    ],
                ],
            ],
            $hookMethods
        );
    }
}
