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
use Symfony\UX\Router\Command\WarmCacheCommand;
use Symfony\UX\Router\RouteNormalizer;
use Symfony\UX\Router\RoutesDumper;
use Symfony\UX\Router\TypeScriptRoutePrinter;

/*
 * @author Hugo Alliaume <hugo@alliau.me>
 */
return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('ux.router.cache_warmer.routes_cache_warmer', RoutesCacheWarmer::class)
            ->args([
                service('router'),
                service('ux.router.routes_dumper'),
                abstract_arg('dump_directory'),
                abstract_arg('dump_typescript'),
                abstract_arg('routes_patterns'),
            ])
            ->tag('kernel.cache_warmer')

        ->set('ux.router.routes_dumper', RoutesDumper::class)
            ->args([
                service('ux.router.route_normalizer'),
                service('ux.router.typescript_route_printer'),
                service('filesystem'),
            ])

        ->set('ux.router.route_normalizer', RouteNormalizer::class)

        ->set('ux.router.typescript_route_printer', TypeScriptRoutePrinter::class)

        ->set('.ux_router.command.warm_cache', WarmCacheCommand::class)
            ->args([
                service('ux.router.cache_warmer.routes_cache_warmer'),
                param('kernel.cache_dir'),
            ])
            ->tag('console.command')
    ;
};
