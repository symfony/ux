<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Router\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\UX\Router\Tests\Kernel\EmptyAppKernel;
use Symfony\UX\Router\Tests\Kernel\FrameworkAppKernel;
use Symfony\UX\Router\UXRouterBundle;

class UXRouterBundleTest extends TestCase
{
    public static function provideKernels()
    {
        yield 'empty' => [new EmptyAppKernel('test', true)];
        yield 'framework' => [new FrameworkAppKernel('test', true)];
    }

    #[DataProvider('provideKernels')]
    public function testBootKernel(Kernel $kernel): void
    {
        $kernel->boot();
        $this->assertArrayHasKey('UXRouterBundle', $kernel->getBundles());
    }

    public function testDefaultConfiguration(): void
    {
        self::assertSame([
            'dump_directory' => '%kernel.project_dir%/var/routes',
            'dump_typescript' => true,
            'routes' => [],
        ], $this->processConfiguration([]));
    }

    public function testRoutesAsString(): void
    {
        self::assertSame(['app_*'], $this->processConfiguration([['routes' => 'app_*']])['routes']);
    }

    public function testRoutesAsList(): void
    {
        self::assertSame(['app_*', '!app_admin_*'], $this->processConfiguration([['routes' => ['app_*', '!app_admin_*']]])['routes']);
    }

    #[TestWith([true])]
    #[TestWith([false])]
    public function testRefreshListenerIsRegisteredInDebugOnly(bool $debug): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.build_dir', sys_get_temp_dir().'/build');
        $container->setParameter('kernel.debug', $debug);
        new UXRouterBundle()->getContainerExtension()->load([['routes' => ['app_*']]], $container);

        self::assertSame($debug, $container->hasDefinition('ux.router.event_listener.refresh_routes'));
    }

    public function testRefreshListenerReceivesTheConfiguration(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.build_dir', sys_get_temp_dir().'/build');
        $container->setParameter('kernel.debug', true);
        new UXRouterBundle()->getContainerExtension()->load([['routes' => ['app_*'], 'dump_typescript' => false]], $container);

        $arguments = $container->getDefinition('ux.router.event_listener.refresh_routes')->getArguments();
        self::assertSame(['%kernel.project_dir%/var/routes', false, ['app_*']], \array_slice($arguments, 4));
    }

    /**
     * @param list<array<string, mixed>> $configs
     *
     * @return array<string, mixed>
     */
    private function processConfiguration(array $configs): array
    {
        $configuration = new UXRouterBundle()->getContainerExtension()->getConfiguration([], new ContainerBuilder());

        return new Processor()->processConfiguration($configuration, $configs);
    }
}
