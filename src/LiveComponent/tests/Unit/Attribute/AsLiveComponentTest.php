<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Unit\Attribute;

use PHPUnit\Framework\TestCase;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\LiveComponent\Attribute\PreDehydrate;
use Symfony\UX\LiveComponent\Attribute\PreReRender;
use Symfony\UX\LiveComponent\Tests\Fixtures\Component\Component5;
use Symfony\UX\LiveComponent\Tests\Fixtures\Component\ComponentWithRepeatedLiveListener;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class AsLiveComponentTest extends TestCase
{
    public function testPreDehydrateMethodsAreOrderedByPriority(): void
    {
        $hooks = AsLiveComponent::preDehydrateMethods(
            new class() {
                #[PreDehydrate(priority: -10)]
                public function hook1()
                {
                }

                #[PreDehydrate(priority: 10)]
                public function hook2()
                {
                }

                #[PreDehydrate]
                public function hook3()
                {
                }
            }
        );

        $this->assertCount(3, $hooks);
        $this->assertSame('hook2', $hooks[0]->name);
        $this->assertSame('hook3', $hooks[1]->name);
        $this->assertSame('hook1', $hooks[2]->name);
    }

    public function testPostHydrateMethodsAreOrderedByPriority(): void
    {
        $hooks = AsLiveComponent::postHydrateMethods(
            new class() {
                #[PostHydrate(priority: -10)]
                public function hook1()
                {
                }

                #[PostHydrate(priority: 10)]
                public function hook2()
                {
                }

                #[PostHydrate]
                public function hook3()
                {
                }
            }
        );

        $this->assertCount(3, $hooks);
        $this->assertSame('hook2', $hooks[0]->name);
        $this->assertSame('hook3', $hooks[1]->name);
        $this->assertSame('hook1', $hooks[2]->name);
    }

    public function testPreMountHooksAreOrderedByPriority(): void
    {
        $hooks = AsLiveComponent::preReRenderMethods(
            new class() {
                #[PreReRender(priority: -10)]
                public function hook1()
                {
                }

                #[PreReRender(priority: 10)]
                public function hook2()
                {
                }

                #[PreReRender]
                public function hook3()
                {
                }
            }
        );

        $this->assertCount(3, $hooks);
        $this->assertSame('hook2', $hooks[0]->name);
        $this->assertSame('hook3', $hooks[1]->name);
        $this->assertSame('hook1', $hooks[2]->name);
    }

    public function testCanGetPostHydrateMethodsFromClassString(): void
    {
        $methods = AsLiveComponent::postHydrateMethods(DummyLiveComponent::class);

        $this->assertCount(1, $methods);
        $this->assertSame('method', $methods[0]->getName());
        $this->assertSame(DummyLiveComponent::class, $methods[0]->getDeclaringClass()?->getName());
    }

    public function testCanGetLiveListeners(): void
    {
        $liveListeners = AsLiveComponent::liveListeners(new Component5());

        $this->assertCount(1, $liveListeners);
        $this->assertSame([
            'action' => 'aListenerActionMethod',
            'event' => 'the_event_name',
        ], $liveListeners[0]);
    }

    public function testCanGetLiveListenersFromClassString(): void
    {
        $liveListeners = AsLiveComponent::liveListeners(DummyLiveComponent::class);

        $this->assertCount(1, $liveListeners);
        $this->assertSame([
            'action' => 'method',
            'event' => 'event_name',
        ], $liveListeners[0]);
    }

    public function testCanGetRepeatedLiveListeners(): void
    {
        $liveListeners = AsLiveComponent::liveListeners(new ComponentWithRepeatedLiveListener());

        $this->assertCount(4, $liveListeners);
        $this->assertSame([
            [
                'action' => 'onBar',
                'event' => 'bar',
            ],
            [
                'action' => 'onFooBar',
                'event' => 'foo',
            ],
            [
                'action' => 'onFooBar',
                'event' => 'bar',
            ],
            [
                'action' => 'onFooBar',
                'event' => 'foo:bar',
            ],
        ], $liveListeners);
    }

    public function testCanGetRepeatedLiveListenersFromClassString(): void
    {
        $liveListeners = AsLiveComponent::liveListeners(ComponentWithRepeatedLiveListener::class);

        $this->assertCount(4, $liveListeners);
    }

    public function testCanCheckIfMethodIsAllowed(): void
    {
        $component = new Component5();

        $this->assertTrue(AsLiveComponent::isActionAllowed($component, 'method1'));
        $this->assertFalse(AsLiveComponent::isActionAllowed($component, 'method2'));
        $this->assertTrue(AsLiveComponent::isActionAllowed($component, 'aListenerActionMethod'));
    }
}

#[AsLiveComponent]
class DummyLiveComponent
{
    #[PreDehydrate]
    #[PreReRender]
    #[PostHydrate]
    #[LiveListener('event_name')]
    #[LiveAction]
    public function method(): bool
    {
        return true;
    }
}
