<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Router\Tests\CacheWarmer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\UX\Router\CacheWarmer\RoutesCacheWarmer;
use Symfony\UX\Router\RoutesDumper;

final class RoutesCacheWarmerTest extends TestCase
{
    public function testWarmUp(): void
    {
        $routes = new RouteCollection();
        $routes->add('app_home', new Route('/'));

        $router = $this->createStub(RouterInterface::class);
        $router->method('getRouteCollection')->willReturn($routes);

        $routesDumper = $this->createMock(RoutesDumper::class);
        $routesDumper
            ->expects($this->once())
            ->method('dump')
            ->with($routes, '/tmp/routes', true, ['app_*']);

        $cacheWarmer = new RoutesCacheWarmer($router, $routesDumper, '/tmp/routes', true, ['app_*']);

        self::assertTrue($cacheWarmer->isOptional());
        self::assertSame([], $cacheWarmer->warmUp(sys_get_temp_dir()));
    }
}
