<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel implements CompilerPassInterface
{
    use MicroKernelTrait { MicroKernelTrait::configureRoutes as doConfigureRoutes; }

    public function process(ContainerBuilder $container): void
    {
        // TODO: Add support for multiple UX Map renderers
        $mapRenderers = $container->getDefinition('ux_map.renderers');
        $mapRenderers->setArgument(0, [
            MapRenderer::Leaflet->value => 'leaflet://default',
            MapRenderer::Google->value => 'google://not-an-api-key@default',

            // Since using Google Maps cost money, you need to use your own Google Maps API settings:
            // 1. Create your own API on https://developers.google.com/maps/documentation/javascript/get-api-key, scope it to `127.0.0.1:9876/*`
            // 2. Create a default map ID on https://developers.google.com/maps/documentation/javascript/map-ids/get-map-id
            // 3. Update the `.env.local` file and define env vars `GOOGLE_MAPS_API_KEY` and `GOOGLE_MAPS_DEFAULT_MAP_ID`
            // 4. Uncomment the line below
            // MapRenderer::Google->value => 'google://'.$container->resolveEnvPlaceholders('%env(GOOGLE_MAPS_API_KEY)%').'@default',
        ]);
    }

    public function configureRoutes(RoutingConfigurator $routes): void
    {
        $this->doConfigureRoutes($routes);

        $extension = self::VERSION_ID < 70000 ? 'xml' : 'php';

        $routes->import('@FrameworkBundle/Resources/config/routing/errors.'.$extension)->prefix('/_error');

        if ('dev' === $this->environment) {
            $routes->import('@WebProfilerBundle/Resources/config/routing/profiler.'.$extension)->prefix('/_profiler');
            $routes->import('@WebProfilerBundle/Resources/config/routing/wdt.'.$extension)->prefix('/_wdt');
        }
    }
}
