<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Bridge\Leaflet\Tests;

use Symfony\UX\Map\Bridge\Leaflet\Renderer\LeafletRenderer;
use Symfony\UX\Map\InfoWindow;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;
use Symfony\UX\Map\Test\RendererTestCase;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;

class LeafletRendererTest extends RendererTestCase
{
    public function provideTestRenderMap(): iterable
    {
        $map = (new Map())
            ->center(new Point(48.8566, 2.3522))
            ->zoom(12);

        yield 'simple map' => [
            'expected_render' => '<div data-controller="symfony--ux-leaflet-map--map" data-symfony--ux-leaflet-map--map-provider-options-value="{}" data-symfony--ux-leaflet-map--map-view-value="{&quot;center&quot;:{&quot;lat&quot;:48.8566,&quot;lng&quot;:2.3522},&quot;zoom&quot;:12,&quot;fitBoundsToMarkers&quot;:false,&quot;options&quot;:{&quot;tileLayer&quot;:{&quot;url&quot;:&quot;https:\/\/tile.openstreetmap.org\/{z}\/{x}\/{y}.png&quot;,&quot;attribution&quot;:&quot;\u00a9 &lt;a href=\&quot;https:\/\/www.openstreetmap.org\/copyright\&quot;&gt;OpenStreetMap&lt;\/a&gt;&quot;,&quot;options&quot;:{}}},&quot;markers&quot;:[],&quot;polygons&quot;:[]}"></div>',
            'renderer' => new LeafletRenderer(new StimulusHelper(null)),
            'map' => $map,
        ];

        yield 'with custom attributes' => [
            'expected_render' => '<div data-controller="my-custom-controller symfony--ux-leaflet-map--map" data-symfony--ux-leaflet-map--map-provider-options-value="{}" data-symfony--ux-leaflet-map--map-view-value="{&quot;center&quot;:{&quot;lat&quot;:48.8566,&quot;lng&quot;:2.3522},&quot;zoom&quot;:12,&quot;fitBoundsToMarkers&quot;:false,&quot;options&quot;:{&quot;tileLayer&quot;:{&quot;url&quot;:&quot;https:\/\/tile.openstreetmap.org\/{z}\/{x}\/{y}.png&quot;,&quot;attribution&quot;:&quot;\u00a9 &lt;a href=\&quot;https:\/\/www.openstreetmap.org\/copyright\&quot;&gt;OpenStreetMap&lt;\/a&gt;&quot;,&quot;options&quot;:{}}},&quot;markers&quot;:[],&quot;polygons&quot;:[]}" class="map"></div>',
            'renderer' => new LeafletRenderer(new StimulusHelper(null)),
            'map' => $map,
            'attributes' => ['data-controller' => 'my-custom-controller', 'class' => 'map'],
        ];

        yield 'with markers and infoWindows' => [
            'expected_render' => '<div data-controller="symfony--ux-leaflet-map--map" data-symfony--ux-leaflet-map--map-provider-options-value="{}" data-symfony--ux-leaflet-map--map-view-value="{&quot;center&quot;:{&quot;lat&quot;:48.8566,&quot;lng&quot;:2.3522},&quot;zoom&quot;:12,&quot;fitBoundsToMarkers&quot;:false,&quot;options&quot;:{&quot;tileLayer&quot;:{&quot;url&quot;:&quot;https:\/\/tile.openstreetmap.org\/{z}\/{x}\/{y}.png&quot;,&quot;attribution&quot;:&quot;\u00a9 &lt;a href=\&quot;https:\/\/www.openstreetmap.org\/copyright\&quot;&gt;OpenStreetMap&lt;\/a&gt;&quot;,&quot;options&quot;:{}}},&quot;markers&quot;:[{&quot;position&quot;:{&quot;lat&quot;:48.8566,&quot;lng&quot;:2.3522},&quot;title&quot;:&quot;Paris&quot;,&quot;infoWindow&quot;:null,&quot;extra&quot;:{}},{&quot;position&quot;:{&quot;lat&quot;:48.8566,&quot;lng&quot;:2.3522},&quot;title&quot;:&quot;Lyon&quot;,&quot;infoWindow&quot;:{&quot;headerContent&quot;:null,&quot;content&quot;:&quot;Lyon&quot;,&quot;position&quot;:null,&quot;opened&quot;:false,&quot;autoClose&quot;:true,&quot;extra&quot;:{}},&quot;extra&quot;:{}}],&quot;polygons&quot;:[]}"></div>',
            'renderer' => new LeafletRenderer(new StimulusHelper(null)),
            'map' => (clone $map)
                ->addMarker(new Marker(new Point(48.8566, 2.3522), 'Paris'))
                ->addMarker(new Marker(new Point(48.8566, 2.3522), 'Lyon', infoWindow: new InfoWindow(content: 'Lyon'))),
        ];
    }
}
