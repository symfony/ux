<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Functional\Test;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
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

    public function testCanEmitEvent(): void
    {
        $testComponent = $this->createLiveComponent('component2');

        $this->assertStringContainsString('Count: 1', $testComponent->render());
        $this->assertSame(1, $testComponent->component()->count);

        $testComponent->emit('triggerIncrease', ['amount' => 2]);

        $this->assertStringContainsString('Count: 5', $testComponent->render());
        $this->assertSame(5, $testComponent->component()->count);
    }

    public function testInvalidEventName(): void
    {
        $testComponent = $this->createLiveComponent('component2');

        $this->expectException(\InvalidArgumentException::class);

        $testComponent->emit('invalid');
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

    public function testRenderingIsLazy(): void
    {
        if (!class_exists(IsGranted::class)) {
            $this->markTestSkipped('The security attributes are not available.');
        }

        $testComponent = $this->createLiveComponent('with_security');

        $this->expectException(AccessDeniedException::class);

        $testComponent->render();
    }

    public function testActingAs(): void
    {
        $testComponent = $this->createLiveComponent('with_security')
            ->actingAs(new InMemoryUser('kevin', 'pass', ['ROLE_USER']))
        ;

        $this->assertStringNotContainsString('Username: kevin', $testComponent->render());

        $testComponent->call('setUsername');

        $this->assertStringContainsString('Username: kevin', $testComponent->render());
    }

    public function testCanSubmitForm(): void
    {
        $testComponent = $this->createLiveComponent('form_with_many_different_fields_type');

        $response = $testComponent->submitForm(['form' => ['text' => 'foobar']])->response();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('foobar', $testComponent->render());
    }
}
