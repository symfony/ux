<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace src\Turbo\tests\Compiler;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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
        $this->assertTrue($container->has('turbo.mercure.hub.broadcaster'));
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
    }
}
