<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Router\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\ConfigCacheFactory;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\UX\Router\EventListener\RefreshRoutesListener;
use Symfony\UX\Router\RouteNormalizer;
use Symfony\UX\Router\RoutesDumper;
use Symfony\UX\Router\TypeScriptRoutePrinter;

final class RefreshRoutesListenerTest extends TestCase
{
    private string $dir;
    private string $resource;
    private RouteCollection $routes;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/ux_router_refresh_'.bin2hex(random_bytes(4));
        $this->resource = $this->dir.'/routes.php';
        new Filesystem()->dumpFile($this->resource, '<?php');
        $this->routes = $this->createRoutes(['app_home']);
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->dir);
    }

    public function testDumpsOnFirstRequest(): void
    {
        ($this->createListener(['app_*']))($this->createEvent());

        self::assertStringContainsString('"app_home"', file_get_contents($this->dir.'/routes/index.js'));
        self::assertFileExists($this->dir.'/routes/index.d.ts');
        self::assertDirectoryDoesNotExist($this->dir.'/routes/ux_router');
    }

    public function testDoesNotDumpAgainWhileRoutesAreFresh(): void
    {
        $listener = $this->createListener(['app_*']);
        $listener($this->createEvent());

        $this->routes = $this->createRoutes(['app_home', 'app_new']);
        $listener($this->createEvent());

        self::assertStringNotContainsString('"app_new"', file_get_contents($this->dir.'/routes/index.js'));
    }

    public function testDumpsAgainWhenARouteResourceChanges(): void
    {
        $listener = $this->createListener(['app_*']);
        $listener($this->createEvent());

        $this->routes = $this->createRoutes(['app_home', 'app_new']);
        touch($this->resource, time() + 10);
        $listener($this->createEvent());

        self::assertStringContainsString('"app_new"', file_get_contents($this->dir.'/routes/index.js'));
    }

    public function testDumpsAgainWhenTheDumpedFileWasRemoved(): void
    {
        $listener = $this->createListener(['app_*']);
        $listener($this->createEvent());

        unlink($this->dir.'/routes/index.js');
        $listener($this->createEvent());

        self::assertFileExists($this->dir.'/routes/index.js');
    }

    public function testDumpsAgainWhenTheConfigurationChanges(): void
    {
        ($this->createListener(['app_*']))($this->createEvent());
        ($this->createListener(['!app_*']))($this->createEvent());

        self::assertStringNotContainsString('"app_home"', file_get_contents($this->dir.'/routes/index.js'));
    }

    public function testDumpsAgainWhenSwitchingBackToAPreviousConfiguration(): void
    {
        ($this->createListener(['app_*']))($this->createEvent());
        ($this->createListener(['!app_*']))($this->createEvent());
        ($this->createListener(['app_*']))($this->createEvent());

        self::assertStringContainsString('"app_home"', file_get_contents($this->dir.'/routes/index.js'));
    }

    public function testDumpsAgainWhenTheDumpedFileWasModified(): void
    {
        $listener = $this->createListener(['app_*']);
        $listener($this->createEvent());

        file_put_contents($this->dir.'/routes/index.js', 'export const routes = {};');
        $listener($this->createEvent());

        self::assertStringContainsString('"app_home"', file_get_contents($this->dir.'/routes/index.js'));
    }

    public function testIgnoresSubRequests(): void
    {
        ($this->createListener(['app_*']))($this->createEvent(HttpKernelInterface::SUB_REQUEST));

        self::assertFileDoesNotExist($this->dir.'/routes/index.js');
    }

    /**
     * @param list<string> $patterns
     */
    private function createListener(array $patterns): RefreshRoutesListener
    {
        $router = $this->createStub(RouterInterface::class);
        $router->method('getRouteCollection')->willReturnCallback(fn () => $this->routes);

        return new RefreshRoutesListener(
            $router,
            new RoutesDumper(new RouteNormalizer(), new TypeScriptRoutePrinter(), new Filesystem()),
            new ConfigCacheFactory(true),
            $this->dir.'/cache',
            $this->dir.'/routes',
            true,
            $patterns,
        );
    }

    /**
     * @param list<string> $names
     */
    private function createRoutes(array $names): RouteCollection
    {
        $routes = new RouteCollection();
        foreach ($names as $name) {
            $routes->add($name, new Route('/'.$name));
        }
        $routes->addResource(new FileResource($this->resource));

        return $routes;
    }

    private function createEvent(int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent($this->createStub(HttpKernelInterface::class), new Request(), $requestType);
    }
}
