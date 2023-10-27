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
use Symfony\UX\Router\RoutesDumper;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @experimental
 */
class RoutesCacheWarmer implements CacheWarmerInterface
{
    public function __construct(
        private RoutesDumper $routesDumper,
    ) {
    }

    public function isOptional(): bool
    {
        return true;
    }

    public function warmUp(string $cacheDir, string $buildDir = null): array
    {
        $this->routesDumper->dump(
            // TODO: pass routes collection
        );

        // No need to preload anything
        return [];
    }
}
