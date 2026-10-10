<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Router\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\UX\Router\DependencyInjection\UXRouterExtension;

final class UXRouterExtensionTest extends TestCase
{
    #[TestWith([true])]
    #[TestWith([false])]
    public function testRefreshListenerIsRegisteredInDebugOnly(bool $debug): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', $debug);
        new UXRouterExtension()->load([['routes' => ['app_*']]], $container);

        self::assertSame($debug, $container->hasDefinition('ux.router.event_listener.refresh_routes'));
    }

    public function testRefreshListenerReceivesTheConfiguration(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', true);
        new UXRouterExtension()->load([['routes' => ['app_*'], 'dump_typescript' => false]], $container);

        $arguments = $container->getDefinition('ux.router.event_listener.refresh_routes')->getArguments();
        self::assertSame(['%kernel.project_dir%/var/routes', false, ['app_*']], \array_slice($arguments, 4));
    }
}
