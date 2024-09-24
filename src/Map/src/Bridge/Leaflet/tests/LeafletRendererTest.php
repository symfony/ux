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
            'expected_render' => '<div data-controller="symfony--ux-leaflet-map--map" data-symfony--ux-leaflet-map--map-provider-options-value="&#x7B;&#x7D;" data-symfony--ux-leaflet-map--map-view-value="&#x7B;&quot;center&quot;&#x3A;&#x7B;&quot;lat&quot;&#x3A;48.8566,&quot;lng&quot;&#x3A;2.3522&#x7D;,&quot;zoom&quot;&#x3A;12,&quot;fitBoundsToMarkers&quot;&#x3A;false,&quot;options&quot;&#x3A;&#x7B;&quot;tileLayer&quot;&#x3A;&#x7B;&quot;url&quot;&#x3A;&quot;https&#x3A;&#x5C;&#x2F;&#x5C;&#x2F;tile.openstreetmap.org&#x5C;&#x2F;&#x7B;z&#x7D;&#x5C;&#x2F;&#x7B;x&#x7D;&#x5C;&#x2F;&#x7B;y&#x7D;.png&quot;,&quot;attribution&quot;&#x3A;&quot;&#x5C;u00a9&#x20;&lt;a&#x20;href&#x3D;&#x5C;&quot;https&#x3A;&#x5C;&#x2F;&#x5C;&#x2F;www.openstreetmap.org&#x5C;&#x2F;copyright&#x5C;&quot;&gt;OpenStreetMap&lt;&#x5C;&#x2F;a&gt;&quot;,&quot;options&quot;&#x3A;&#x7B;&#x7D;&#x7D;&#x7D;,&quot;markers&quot;&#x3A;&#x5B;&#x5D;,&quot;polygons&quot;&#x3A;&#x5B;&#x5D;&#x7D;"></div>',
            'renderer' => new LeafletRenderer(new StimulusHelper(null)),
            'map' => $map,
        ];

        yield 'with custom attributes' => [
            'expected_render' => '<div data-controller="my-custom-controller symfony--ux-leaflet-map--map" data-symfony--ux-leaflet-map--map-provider-options-value="&#x7B;&#x7D;" data-symfony--ux-leaflet-map--map-view-value="&#x7B;&quot;center&quot;&#x3A;&#x7B;&quot;lat&quot;&#x3A;48.8566,&quot;lng&quot;&#x3A;2.3522&#x7D;,&quot;zoom&quot;&#x3A;12,&quot;fitBoundsToMarkers&quot;&#x3A;false,&quot;options&quot;&#x3A;&#x7B;&quot;tileLayer&quot;&#x3A;&#x7B;&quot;url&quot;&#x3A;&quot;https&#x3A;&#x5C;&#x2F;&#x5C;&#x2F;tile.openstreetmap.org&#x5C;&#x2F;&#x7B;z&#x7D;&#x5C;&#x2F;&#x7B;x&#x7D;&#x5C;&#x2F;&#x7B;y&#x7D;.png&quot;,&quot;attribution&quot;&#x3A;&quot;&#x5C;u00a9&#x20;&lt;a&#x20;href&#x3D;&#x5C;&quot;https&#x3A;&#x5C;&#x2F;&#x5C;&#x2F;www.openstreetmap.org&#x5C;&#x2F;copyright&#x5C;&quot;&gt;OpenStreetMap&lt;&#x5C;&#x2F;a&gt;&quot;,&quot;options&quot;&#x3A;&#x7B;&#x7D;&#x7D;&#x7D;,&quot;markers&quot;&#x3A;&#x5B;&#x5D;,&quot;polygons&quot;&#x3A;&#x5B;&#x5D;&#x7D;" class="map"></div>',
            'renderer' => new LeafletRenderer(new StimulusHelper(null)),
            'map' => $map,
            'attributes' => ['data-controller' => 'my-custom-controller', 'class' => 'map'],
        ];

        yield 'with markers and infoWindows' => [
            'expected_render' => '<div data-controller="symfony--ux-leaflet-map--map" data-symfony--ux-leaflet-map--map-provider-options-value="&#x7B;&#x7D;" data-symfony--ux-leaflet-map--map-view-value="&#x7B;&quot;center&quot;&#x3A;&#x7B;&quot;lat&quot;&#x3A;48.8566,&quot;lng&quot;&#x3A;2.3522&#x7D;,&quot;zoom&quot;&#x3A;12,&quot;fitBoundsToMarkers&quot;&#x3A;false,&quot;options&quot;&#x3A;&#x7B;&quot;tileLayer&quot;&#x3A;&#x7B;&quot;url&quot;&#x3A;&quot;https&#x3A;&#x5C;&#x2F;&#x5C;&#x2F;tile.openstreetmap.org&#x5C;&#x2F;&#x7B;z&#x7D;&#x5C;&#x2F;&#x7B;x&#x7D;&#x5C;&#x2F;&#x7B;y&#x7D;.png&quot;,&quot;attribution&quot;&#x3A;&quot;&#x5C;u00a9&#x20;&lt;a&#x20;href&#x3D;&#x5C;&quot;https&#x3A;&#x5C;&#x2F;&#x5C;&#x2F;www.openstreetmap.org&#x5C;&#x2F;copyright&#x5C;&quot;&gt;OpenStreetMap&lt;&#x5C;&#x2F;a&gt;&quot;,&quot;options&quot;&#x3A;&#x7B;&#x7D;&#x7D;&#x7D;,&quot;markers&quot;&#x3A;&#x5B;&#x7B;&quot;position&quot;&#x3A;&#x7B;&quot;lat&quot;&#x3A;48.8566,&quot;lng&quot;&#x3A;2.3522&#x7D;,&quot;title&quot;&#x3A;&quot;Paris&quot;,&quot;infoWindow&quot;&#x3A;null,&quot;extra&quot;&#x3A;&#x7B;&#x7D;&#x7D;,&#x7B;&quot;position&quot;&#x3A;&#x7B;&quot;lat&quot;&#x3A;48.8566,&quot;lng&quot;&#x3A;2.3522&#x7D;,&quot;title&quot;&#x3A;&quot;Lyon&quot;,&quot;infoWindow&quot;&#x3A;&#x7B;&quot;headerContent&quot;&#x3A;null,&quot;content&quot;&#x3A;&quot;Lyon&quot;,&quot;position&quot;&#x3A;null,&quot;opened&quot;&#x3A;false,&quot;autoClose&quot;&#x3A;true,&quot;extra&quot;&#x3A;&#x7B;&#x7D;&#x7D;,&quot;extra&quot;&#x3A;&#x7B;&#x7D;&#x7D;&#x5D;,&quot;polygons&quot;&#x3A;&#x5B;&#x5D;&#x7D;"></div>',
            'renderer' => new LeafletRenderer(new StimulusHelper(null)),
            'map' => (clone $map)
                ->addMarker(new Marker(new Point(48.8566, 2.3522), 'Paris'))
                ->addMarker(new Marker(new Point(48.8566, 2.3522), 'Lyon', infoWindow: new InfoWindow(content: 'Lyon'))),
        ];
    }
}
