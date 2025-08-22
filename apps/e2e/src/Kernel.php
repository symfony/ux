<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel implements CompilerPassInterface
{
    use MicroKernelTrait;

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
            //MapRenderer::Google->value => 'google://'.$container->resolveEnvPlaceholders('%env(GOOGLE_MAPS_API_KEY)%').'@default',
        ]);
    }
}
