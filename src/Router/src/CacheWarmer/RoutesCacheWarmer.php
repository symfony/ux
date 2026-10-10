<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Router\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\UX\Router\RoutesDumper;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
class RoutesCacheWarmer implements CacheWarmerInterface
{
    /**
     * @param list<string> $routesPatterns
     */
    public function __construct(
        private RouterInterface $router,
        private RoutesDumper $routesDumper,
        private string $dumpDir,
        private bool $dumpTypeScript,
        private array $routesPatterns,
    ) {
    }

    public function isOptional(): bool
    {
        return true;
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $this->routesDumper->dump(
            $this->router->getRouteCollection(),
            $this->dumpDir,
            $this->dumpTypeScript,
            $this->routesPatterns,
        );

        return [];
    }
}
