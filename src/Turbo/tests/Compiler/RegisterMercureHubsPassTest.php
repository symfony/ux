<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Tests\Compiler;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\UX\Turbo\Bridge\Mercure\TurboStreamListenRenderer;
use Symfony\UX\Turbo\DependencyInjection\Compiler\RegisterMercureHubsPass;

final class RegisterMercureHubsPassTest extends TestCase
{
    public function testProcess(): void
    {
        $pass = new RegisterMercureHubsPass();

        $container = new ContainerBuilder();
        $container->register('hub')
            ->addTag('mercure.hub');

        $pass->process($container);

        $this->assertTrue($container->has('turbo.mercure.hub.renderer'));
        $this->assertTrue($container->has('turbo.mercure.hub.stream_source_renderer'));
        $this->assertTrue($container->has('turbo.mercure.hub.broadcaster'));
    }

    public function testRendererServiceIsDeprecated(): void
    {
        $pass = new RegisterMercureHubsPass();

        $container = new ContainerBuilder();
        $container->register('hub')
            ->addTag('mercure.hub');

        $pass->process($container);

        $this->assertTrue($container->getDefinition('turbo.mercure.hub.renderer')->isDeprecated());
        $this->assertSame(
            'The "turbo.mercure.hub.renderer" service is deprecated since Symfony UX 3.1, use "turbo.mercure.hub.stream_source_renderer" with turbo_stream_from() or the <twig:Turbo:Stream:From> Twig component instead. It will be removed in 4.0.',
            $container->getDefinition('turbo.mercure.hub.renderer')->getDeprecation('turbo.mercure.hub.renderer')['message'],
        );
        $this->assertFalse($container->getDefinition('turbo.mercure.hub.stream_source_renderer')->isDeprecated());
    }

    /**
     * The renderer is registered for every hub, and the container compilation loads its class (e.g. to read its
     * attributes): loading it must not trigger a deprecation, for applications not using turbo_stream_listen().
     */
    #[RunInSeparateProcess]
    public function testLoadingRendererClassDoesNotTriggerDeprecation(): void
    {
        $deprecations = [];
        set_error_handler(static function (int $type, string $message) use (&$deprecations): bool {
            $deprecations[] = $message;

            return true;
        }, \E_USER_DEPRECATED);

        try {
            $this->assertTrue(class_exists(TurboStreamListenRenderer::class));
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $deprecations);
    }

    public function testProcessWithDefault(): void
    {
        $pass = new RegisterMercureHubsPass();

        $container = new ContainerBuilder();
        $container->register('hub1')
            ->addTag('mercure.hub');

        $container->register('default_hub')
            ->addTag('mercure.hub', ['default' => true]);

        $pass->process($container);

        $this->assertSame([
            'transport' => 'default',
        ], $container->getDefinition('turbo.mercure.default_hub.renderer')->getTag('turbo.renderer.stream_listen')[1]);

        $this->assertSame([
            'transport' => 'default',
        ], $container->getDefinition('turbo.mercure.default_hub.stream_source_renderer')->getTag('turbo.stream_source_renderer')[1]);
    }
}
