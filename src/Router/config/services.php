<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\UX\Router\CacheWarmer\RoutesCacheWarmer;
use Symfony\UX\Router\RoutesDumper;

/*
 * @author Hugo Alliaume <hugo@alliau.me>
 */
return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('ux.router.cache_warmer.routes_cache_warmer', RoutesCacheWarmer::class)
            ->args([
                service('router'),
                service('ux.router.routes_dumper'),
            ])
            ->tag('kernel.cache_warmer')

        ->set('ux.router.routes_dumper', RoutesDumper::class)
            ->args([
                null, // Dump directory
                service('filesystem'),
            ])
    ;
};
