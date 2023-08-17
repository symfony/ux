<?php

namespace Symfony\UX\LiveComponent\Tests\Functional\Test;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Tests\Fixtures\Component\Component2;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class InteractsWithLiveComponentsTest extends KernelTestCase
{
    use InteractsWithLiveComponents;

    public function testCanRenderInitialData(): void
    {
        $testComponent = $this->createLiveComponent('component2');

        $this->assertStringContainsString('Count: 1', (string) $testComponent->render());
        $this->assertSame(1, $testComponent->component()->count);
    }

    public function testCanCreateWithClassString(): void
    {
        $testComponent = $this->createLiveComponent(Component2::class);

        $this->assertStringContainsString('Count: 1', (string) $testComponent->render());
        $this->assertSame(1, $testComponent->component()->count);
    }

    public function testCanCallLiveAction(): void
    {
        $testComponent = $this->createLiveComponent('component2');

        $this->assertStringContainsString('Count: 1', $testComponent->render());
        $this->assertSame(1, $testComponent->component()->count);

        $testComponent->call('increase');

        $this->assertStringContainsString('Count: 2', $testComponent->render());
        $this->assertSame(2, $testComponent->component()->count);
    }

    public function testCanCallLiveActionWithArguments(): void
    {
        $testComponent = $this->createLiveComponent('component6');

        $this->assertStringContainsString('Arg1: not provided', $testComponent->render());
        $this->assertStringContainsString('Arg2: not provided', $testComponent->render());
        $this->assertStringContainsString('Arg3: not provided', $testComponent->render());
        $this->assertNull($testComponent->component()->arg1);
        $this->assertNull($testComponent->component()->arg2);
        $this->assertNull($testComponent->component()->arg3);

        $testComponent->call('inject', ['arg1' => 'hello', 'arg2' => 666, 'custom' => '33.3']);

        $this->assertStringContainsString('Arg1: hello', $testComponent->render());
        $this->assertStringContainsString('Arg2: 666', $testComponent->render());
        $this->assertStringContainsString('Arg3: 33.3', $testComponent->render());
        $this->assertSame('hello', $testComponent->component()->arg1);
        $this->assertSame(666, $testComponent->component()->arg2);
        $this->assertSame(33.3, $testComponent->component()->arg3);
    }

    public function testCanSetLiveProp(): void
    {
        $testComponent = $this->createLiveComponent('component_with_writable_props');

        $this->assertStringContainsString('Count: 1', $testComponent->render());
        $this->assertSame(1, $testComponent->component()->count);

        $testComponent->set('count', 100);

        $this->assertStringContainsString('Count: 100', $testComponent->render());
        $this->assertSame(100, $testComponent->component()->count);
    }

    public function testCanRefreshComponent(): void
    {
        $testComponent = $this->createLiveComponent('track_renders');

        $this->assertStringContainsString('Re-Render Count: 1', $testComponent->render());

        $testComponent->refresh();

        $this->assertStringContainsString('Re-Render Count: 2', $testComponent->render());

        $testComponent->refresh();

        $this->assertStringContainsString('Re-Render Count: 3', $testComponent->render());
    }

    public function testCanAccessResponse(): void
    {
        $testComponent = $this->createLiveComponent('component2');

        $response = $testComponent->call('redirect')->response();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('1', $response->headers->get('X-Custom-Header'));
    }

    public function testCannotUpdateComponentIfNoLongerInContext(): void
    {
        $testComponent = $this->createLiveComponent('component2')->call('redirect');

        $this->expectException(\LogicException::class);

        $testComponent->call('increase');
    }
}
