<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Unit\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\DependencyInjection\Compiler\ComponentListenerScopesPass;
use Symfony\UX\TwigComponent\Event\PostMountEvent;
use Symfony\UX\TwigComponent\Event\PreMountEvent;
use Symfony\UX\TwigComponent\Tests\Fixtures\EventListener\ComponentEventRecorder;

final class ComponentListenerScopesPassTest extends TestCase
{
    public function testTheScopesOfTheListenersOfAnEventAreMerged(): void
    {
        $container = $this->createContainer([
            'live_listener' => [PostMountEvent::class, ['metadata' => 'live']],
            'data_model_listener' => [PostMountEvent::class, ['props' => ['data-model', 'dataModel']]],
        ]);

        new ComponentListenerScopesPass()->process($container);

        $this->assertSame([
            PostMountEvent::class => ['live', ['data-model' => true, 'dataModel' => true], 2],
        ], $container->getDefinition('ux.twig_component.component_factory')->getArgument(7));
    }

    public function testAnEventWhoseListenersNameDifferentMetadataKeysIsNotScoped(): void
    {
        $container = $this->createContainer([
            'live_listener' => [PreMountEvent::class, ['metadata' => 'live']],
            'other_listener' => [PreMountEvent::class, ['metadata' => 'other']],
        ]);

        new ComponentListenerScopesPass()->process($container);

        $this->assertSame([], $container->getDefinition('ux.twig_component.component_factory')->getArgument(7));
    }

    /**
     * @param array<string, array{class-string, array<string, mixed>}> $listeners
     */
    private function createContainer(array $listeners): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->register('event_dispatcher', EventDispatcher::class);
        $container->register('ux.twig_component.component_factory', ComponentFactory::class)
            ->setArguments([null, null, null, null, [], [], null, []]);

        foreach ($listeners as $id => [$eventClass, $scope]) {
            $container->register($id, ComponentEventRecorder::class)
                ->addTag('kernel.event_listener', ['event' => $eventClass])
                ->addTag(ComponentListenerScopesPass::SCOPED_LISTENER_TAG, $scope);
        }

        new RegisterListenersPass()->process($container);

        return $container;
    }
}
