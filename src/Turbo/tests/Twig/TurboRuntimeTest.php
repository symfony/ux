<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ServiceLocatorTrait;
use Symfony\Contracts\Service\ServiceProviderInterface;
use Symfony\UX\Turbo\StreamSourceRendererInterface;
use Symfony\UX\Turbo\TurboFrame;
use Symfony\UX\Turbo\Twig\TurboRuntime;
use Symfony\UX\Turbo\Twig\TurboStreamListenRendererInterface;
use Twig\Environment;

final class TurboRuntimeTest extends TestCase
{
    public function testRenderTurboStreamFrom()
    {
        $twig = $this->createStub(Environment::class);
        $renderer = $this->createMock(StreamSourceRendererInterface::class);
        $renderer->expects($this->once())
            ->method('render')
            ->with('a_topic', ['private' => false])
            ->willReturn('<turbo-mercure-stream-source src="https://hub/.well-known/mercure?topic=a_topic"></turbo-mercure-stream-source>')
        ;
        $streamSourceRenderers = new class(['default' => static fn () => $renderer]) implements ServiceProviderInterface {
            use ServiceLocatorTrait;
        };
        $renderers = new class([]) implements ServiceProviderInterface {
            use ServiceLocatorTrait;
        };

        $runtime = new TurboRuntime($renderers, 'default', new TurboFrame(new RequestStack()), $streamSourceRenderers);
        $html = $runtime->renderTurboStreamFrom($twig, 'a_topic');

        $this->assertSame(
            '<turbo-mercure-stream-source src="https://hub/.well-known/mercure?topic=a_topic"></turbo-mercure-stream-source>',
            $html,
        );
    }

    public function testRenderTurboStreamFromPrivate()
    {
        $twig = $this->createStub(Environment::class);
        $renderer = $this->createMock(StreamSourceRendererInterface::class);
        $renderer->expects($this->once())
            ->method('render')
            ->with('a_topic', ['private' => true])
            ->willReturn('<turbo-mercure-stream-source src="https://hub/.well-known/mercure?topic=a_topic" private></turbo-mercure-stream-source>')
        ;
        $streamSourceRenderers = new class(['default' => static fn () => $renderer]) implements ServiceProviderInterface {
            use ServiceLocatorTrait;
        };
        $renderers = new class([]) implements ServiceProviderInterface {
            use ServiceLocatorTrait;
        };

        $runtime = new TurboRuntime($renderers, 'default', new TurboFrame(new RequestStack()), $streamSourceRenderers);
        $html = $runtime->renderTurboStreamFrom($twig, 'a_topic', true);

        $this->assertSame(
            '<turbo-mercure-stream-source src="https://hub/.well-known/mercure?topic=a_topic" private></turbo-mercure-stream-source>',
            $html,
        );
    }

    public function testRenderTurboStreamFromUsesCustomRenderer()
    {
        $twig = $this->createStub(Environment::class);
        $renderer = $this->createStub(StreamSourceRendererInterface::class);
        $renderer->method('render')->willReturn('<my-custom-stream-source src="https://my-transport.example.com/subscribe?topic=chat"></my-custom-stream-source>');
        $streamSourceRenderers = new class(['default' => static fn () => $renderer]) implements ServiceProviderInterface {
            use ServiceLocatorTrait;
        };
        $renderers = new class([]) implements ServiceProviderInterface {
            use ServiceLocatorTrait;
        };

        $runtime = new TurboRuntime($renderers, 'default', new TurboFrame(new RequestStack()), $streamSourceRenderers);
        $html = $runtime->renderTurboStreamFrom($twig, 'chat');

        $this->assertSame(
            '<my-custom-stream-source src="https://my-transport.example.com/subscribe?topic=chat"></my-custom-stream-source>',
            $html,
        );
    }

    public function testRenderTurboStreamFromUnknownTransport()
    {
        $this->expectException(\InvalidArgumentException::class);

        $twig = $this->createStub(Environment::class);
        $streamSourceRenderers = new class([]) implements ServiceProviderInterface {
            use ServiceLocatorTrait;
        };
        $renderers = new class([]) implements ServiceProviderInterface {
            use ServiceLocatorTrait;
        };

        $runtime = new TurboRuntime($renderers, 'default', new TurboFrame(new RequestStack()), $streamSourceRenderers);
        $runtime->renderTurboStreamFrom($twig, 'a_topic');
    }

    public function testRenderTurboStreamListen()
    {
        $twig = $this->createStub(Environment::class);
        $renderer = $this->createMock(TurboStreamListenRendererInterface::class);
        $renderer->expects($this->once())
            ->method('renderTurboStreamListen')
            ->willReturn('rendered-attributes')
        ;
        $container = new class(['default' => static fn () => $renderer]) implements ServiceProviderInterface {
            use ServiceLocatorTrait;
        };
        $streamSourceRenderers = new class([]) implements ServiceProviderInterface {
            use ServiceLocatorTrait;
        };

        $runtime = new TurboRuntime($container, 'default', new TurboFrame(new RequestStack()), $streamSourceRenderers);
        $runtime->renderTurboStreamListen($twig, 'a_topic');
    }

    public function testRenderTurboStreamListenWithMultipleHubs()
    {
        $twig = $this->createStub(Environment::class);
        $renderer1 = $this->createStub(TurboStreamListenRendererInterface::class);
        $renderer2 = $this->createMock(TurboStreamListenRendererInterface::class);
        $renderer2->expects($this->once())
            ->method('renderTurboStreamListen')
            ->with($twig, 'a_topic', ['hub' => 'hub2'])
            ->willReturn('rendered-attributes')
        ;
        $container = new class(['hub1' => static fn () => $renderer1, 'hub2' => static fn () => $renderer2]) implements ServiceProviderInterface {
            use ServiceLocatorTrait;
        };
        $streamSourceRenderers = new class([]) implements ServiceProviderInterface {
            use ServiceLocatorTrait;
        };

        $runtime = new TurboRuntime($container, 'hub1', new TurboFrame(new RequestStack()), $streamSourceRenderers);
        $runtime->renderTurboStreamListen($twig, 'a_topic', 'hub2');
    }

    public function testRenderTurboStreamListenWithDifferentsHub()
    {
        $this->expectException(\InvalidArgumentException::class);

        $twig = $this->createStub(Environment::class);
        $renderer = $this->createStub(TurboStreamListenRendererInterface::class);
        $container = new class(['default' => static fn () => $renderer]) implements ServiceProviderInterface {
            use ServiceLocatorTrait;
        };
        $streamSourceRenderers = new class([]) implements ServiceProviderInterface {
            use ServiceLocatorTrait;
        };

        $runtime = new TurboRuntime($container, 'default', new TurboFrame(new RequestStack()), $streamSourceRenderers);
        $runtime->renderTurboStreamListen($twig, 'a_topic', 'default', ['hub' => 'hub3']);
    }
}
