<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Router\EventListener;

use Symfony\Component\Config\ConfigCacheFactoryInterface;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\UX\Router\RoutesDumper;

/**
 * Re-dumps the routes in debug mode when they changed since the last dump.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class RefreshRoutesListener
{
    private string $cachePath;

    /**
     * @param list<string> $routesPatterns
     */
    public function __construct(
        private RouterInterface $router,
        private RoutesDumper $routesDumper,
        private ConfigCacheFactoryInterface $configCacheFactory,
        string $cacheDir,
        private string $dumpDir,
        private bool $dumpTypeScript,
        private array $routesPatterns,
    ) {
        $this->cachePath = $cacheDir.'/ux_router/routes.'.hash('xxh128', serialize([$dumpDir, $dumpTypeScript, $routesPatterns]));
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $dumped = false;

        $cache = $this->configCacheFactory->cache($this->cachePath, function (ConfigCacheInterface $cache) use (&$dumped): void {
            $this->dump($cache);
            $dumped = true;
        });

        // The dumped files can also be rewritten by another configuration, the cache warmer or by hand
        if (!$dumped && file_get_contents($cache->getPath()) !== $this->hashDumpedFiles()) {
            $this->dump($cache);
        }
    }

    private function dump(ConfigCacheInterface $cache): void
    {
        $routes = $this->router->getRouteCollection();
        $this->routesDumper->dump($routes, $this->dumpDir, $this->dumpTypeScript, $this->routesPatterns);

        $cache->write($this->hashDumpedFiles(), $routes->getResources());
    }

    private function hashDumpedFiles(): string
    {
        $context = hash_init('xxh128');

        foreach ($this->dumpTypeScript ? ['index.js', 'index.d.ts'] : ['index.js'] as $file) {
            if (!is_file($this->dumpDir.'/'.$file)) {
                return '';
            }

            hash_update_file($context, $this->dumpDir.'/'.$file);
        }

        return hash_final($context);
    }
}
