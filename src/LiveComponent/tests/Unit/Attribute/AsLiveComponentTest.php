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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
    public function testPreDehydrateMethodsAreOrderedByPriority()
    {
        $hooks = AsLiveComponent::preDehydrateMethods(
            new class {
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

    public function testPostHydrateMethodsAreOrderedByPriority()
    {
        $hooks = AsLiveComponent::postHydrateMethods(
            new class {
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

    public function testPreMountHooksAreOrderedByPriority()
    {
        $hooks = AsLiveComponent::preReRenderMethods(
            new class {
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

    public function testCanGetPostHydrateMethodsFromClassString()
    {
        $methods = AsLiveComponent::postHydrateMethods(DummyLiveComponent::class);

        $this->assertCount(1, $methods);
        $this->assertSame('method', $methods[0]->getName());
        $this->assertSame(DummyLiveComponent::class, $methods[0]->getDeclaringClass()?->getName());
    }

    public function testCanGetLiveListeners()
    {
        $liveListeners = AsLiveComponent::liveListeners(new Component5());

        $this->assertCount(1, $liveListeners);
        $this->assertSame([
            'action' => 'aListenerActionMethod',
            'event' => 'the_event_name',
        ], $liveListeners[0]);
    }

    public function testCanGetLiveListenersFromClassString()
    {
        $liveListeners = AsLiveComponent::liveListeners(DummyLiveComponent::class);

        $this->assertCount(1, $liveListeners);
        $this->assertSame([
            'action' => 'method',
            'event' => 'event_name',
        ], $liveListeners[0]);
    }

    public function testCanGetRepeatedLiveListeners()
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

    public function testCanGetRepeatedLiveListenersFromClassString()
    {
        $liveListeners = AsLiveComponent::liveListeners(ComponentWithRepeatedLiveListener::class);

        $this->assertCount(4, $liveListeners);
    }

    public function testCanCheckIfMethodIsAllowed()
    {
        $component = new Component5();

        $this->assertTrue(AsLiveComponent::isActionAllowed($component, 'method1'));
        $this->assertFalse(AsLiveComponent::isActionAllowed($component, 'method2'));
        $this->assertTrue(AsLiveComponent::isActionAllowed($component, 'aListenerActionMethod'));
    }

    /**
     * @group legacy
     */
    public function testBackwardsCompatibilityWithAllPositionalArgumentsOldSignature()
    {
        // Old signature (before https://github.com/symfony/ux/pull/2251): $csrf was at position 6
        // Position: 1=name, 2=template, 3=defaultAction, 4=exposePublicProps, 5=attributesVar, 6=csrf, 7=route, 8=method, 9=urlReferenceType
        $attribute = new AsLiveComponent(
            'my_component',
            'components/my.html.twig',
            '__invoke',
            true,
            'attrs',
            false,
            'my_custom_route',
            'get',
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->assertFalse($attribute->csrf);
        $this->assertSame('my_custom_route', $attribute->route);
        $this->assertSame('get', $attribute->method);
        $this->assertSame(UrlGeneratorInterface::ABSOLUTE_URL, $attribute->urlReferenceType);
        $this->assertNull($attribute->fetchCredentials); // fetchCredentials didn't exist in old signature
    }

    public function testNewSignatureWithAllPositionalArguments()
    {
        // New signature (after https://github.com/symfony/ux/pull/2251): $fetchCredentials at position 9, $csrf at position 10
        // Position: 1=name, 2=template, 3=defaultAction, 4=exposePublicProps, 5=attributesVar, 6=route, 7=method, 8=urlReferenceType, 9=fetchCredentials, 10=csrf
        $attribute = new AsLiveComponent(
            'my_component',
            'components/my.html.twig',
            '__invoke',
            true,
            'attrs',
            'my_custom_route',
            'get',
            UrlGeneratorInterface::ABSOLUTE_URL,
            'include'
        );

        $this->assertSame('my_custom_route', $attribute->route);
        $this->assertSame('get', $attribute->method);
        $this->assertSame(UrlGeneratorInterface::ABSOLUTE_URL, $attribute->urlReferenceType);
        $this->assertSame('include', $attribute->fetchCredentials);
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

#[AsLiveComponent(method: 'get')]
class GetMethodComponent
{
}
