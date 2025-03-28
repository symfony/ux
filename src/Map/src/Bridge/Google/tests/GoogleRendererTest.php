<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Bridge\Google\Tests;

use Symfony\UX\Icons\IconRendererInterface;
use Symfony\UX\Map\Bridge\Google\GoogleOptions;
use Symfony\UX\Map\Bridge\Google\Renderer\GoogleRenderer;
use Symfony\UX\Map\Icon\Icon;
use Symfony\UX\Map\Icon\UxIconRenderer;
use Symfony\UX\Map\InfoWindow;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;
use Symfony\UX\Map\Polygon;
use Symfony\UX\Map\Polyline;
use Symfony\UX\Map\Test\RendererTestCase;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;

class GoogleRendererTest extends RendererTestCase
{
    public static function provideTestRenderMap(): iterable
    {
        $map = (new Map())
            ->center(new Point(48.8566, 2.3522))
            ->zoom(12);
        $marker1 = new Marker(position: new Point(48.8566, 2.3522), title: 'Paris', id: 'marker1');
        $marker2 = new Marker(position: new Point(48.8566, 2.3522), title: 'Lyon', infoWindow: new InfoWindow(content: 'Lyon'), id: 'marker2');
        $marker3 = new Marker(position: new Point(45.8566, 2.3522), title: 'Dijon', id: 'marker3');

        yield 'simple map, with minimum options' => [
            'renderer' => new GoogleRenderer(new StimulusHelper(null), new UxIconRenderer(null), apiKey: 'api_key'),
            'map' => $map,
        ];

        yield 'with every options' => [
            'renderer' => new GoogleRenderer(new StimulusHelper(null), new UxIconRenderer(null), apiKey: 'api_key', id: 'gmap', language: 'fr', region: 'FR', nonce: 'abcd', retries: 10, url: 'https://maps.googleapis.com/maps/api/js', version: 'quarterly'),
            'map' => $map,
        ];

        yield 'with custom attributes' => [
            'renderer' => new GoogleRenderer(new StimulusHelper(null), new UxIconRenderer(null), apiKey: 'api_key'),
            'map' => $map,
            'attributes' => ['data-controller' => 'my-custom-controller', 'class' => 'map'],
        ];

        yield 'with markers and infoWindows' => [
            'renderer' => new GoogleRenderer(new StimulusHelper(null), new UxIconRenderer(null), apiKey: 'api_key'),
            'map' => (new Map())
                ->center(new Point(48.8566, 2.3522))
                ->zoom(12)
                ->addMarker(new Marker(position: new Point(48.8566, 2.3522), title: 'Paris', id: 'marker1'))
                ->addMarker(new Marker(new Point(48.8566, 2.3522), 'Lyon', infoWindow: new InfoWindow(content: 'Lyon'))),
        ];

        yield 'with all markers removed' => [
            'renderer' => new GoogleRenderer(new StimulusHelper(null), new UxIconRenderer(null), apiKey: 'api_key'),
            'map' => (new Map())
                ->center(new Point(48.8566, 2.3522))
                ->zoom(12)
                ->addMarker($marker1)
                ->addMarker($marker2)
                ->removeMarker($marker1)
                ->removeMarker($marker2),
        ];

        yield 'with marker remove and new ones added' => [
            'renderer' => new GoogleRenderer(new StimulusHelper(null), new UxIconRenderer(null), apiKey: 'api_key'),
            'map' => (new Map())
                ->center(new Point(48.8566, 2.3522))
                ->zoom(12)
                ->addMarker($marker3)
                ->removeMarker($marker3)
                ->addMarker($marker1)
                ->addMarker($marker2),
        ];

        yield 'with polygons and infoWindows' => [
            'renderer' => new GoogleRenderer(new StimulusHelper(null), new UxIconRenderer(null), apiKey: 'api_key'),
            'map' => (new Map())
                ->center(new Point(48.8566, 2.3522))
                ->zoom(12)
                ->addPolygon(new Polygon(points: [new Point(48.8566, 2.3522), new Point(48.8566, 2.3522), new Point(48.8566, 2.3522)]))
                ->addPolygon(new Polygon(points: [new Point(1.1, 2.2), new Point(3.3, 4.4), new Point(5.5, 6.6)], infoWindow: new InfoWindow(content: 'Polygon'))),
        ];

        yield 'with polylines and infoWindows' => [
            'renderer' => new GoogleRenderer(new StimulusHelper(null), new UxIconRenderer(null), apiKey: 'api_key'),
            'map' => (new Map())
                ->center(new Point(48.8566, 2.3522))
                ->zoom(12)
                ->addPolyline(new Polyline(points: [new Point(48.8566, 2.3522), new Point(48.8566, 2.3522), new Point(48.8566, 2.3522)]))
                ->addPolyline(new Polyline(points: [new Point(1.1, 2.2), new Point(3.3, 4.4), new Point(5.5, 6.6)], infoWindow: new InfoWindow(content: 'Polygon'))),
        ];

        yield 'with controls enabled' => [
            'renderer' => new GoogleRenderer(new StimulusHelper(null), new UxIconRenderer(null), apiKey: 'api_key'),
            'map' => (new Map())
                ->center(new Point(48.8566, 2.3522))
                ->zoom(12)
                ->options(new GoogleOptions(
                    zoomControl: true,
                    mapTypeControl: true,
                    streetViewControl: true,
                    fullscreenControl: true,
                )),
        ];

        yield 'without controls enabled' => [
            'renderer' => new GoogleRenderer(new StimulusHelper(null), new UxIconRenderer(null), apiKey: 'api_key'),
            'map' => (new Map())
                ->center(new Point(48.8566, 2.3522))
                ->zoom(12)
                ->options(new GoogleOptions(
                    zoomControl: false,
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: false,
                )),
        ];

        yield 'with default map id' => [
            'renderer' => new GoogleRenderer(new StimulusHelper(null), new UxIconRenderer(null), 'my_api_key', defaultMapId: 'DefaultMapId'),
            'map' => (new Map())
                ->center(new Point(48.8566, 2.3522))
                ->zoom(12),
        ];

        yield 'with default map id, when passing options (except the "mapId")' => [
            'renderer' => new GoogleRenderer(new StimulusHelper(null), new UxIconRenderer(null), 'my_api_key', defaultMapId: 'DefaultMapId'),
            'map' => (new Map())
                ->center(new Point(48.8566, 2.3522))
                ->zoom(12)
                ->options(new GoogleOptions()),
        ];

        yield 'with default map id overridden by option "mapId"' => [
            'renderer' => new GoogleRenderer(new StimulusHelper(null), new UxIconRenderer(null), 'my_api_key', defaultMapId: 'DefaultMapId'),
            'map' => (new Map())
                ->center(new Point(48.8566, 2.3522))
                ->zoom(12)
                ->options(new GoogleOptions(mapId: 'CustomMapId')),
        ];

        yield 'markers with icons' => [
            'renderer' => new GoogleRenderer(
                new StimulusHelper(null),
                new UxIconRenderer(new class implements IconRendererInterface {
                    public function renderIcon(string $name, array $attributes = []): string
                    {
                        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24">...</svg>';
                    }
                }),
                'my_api_key'
            ),
            'map' => (new Map())
                ->center(new Point(48.8566, 2.3522))
                ->zoom(12)
                ->addMarker(new Marker(position: new Point(48.8566, 2.3522), title: 'Paris', icon: Icon::url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/icons/geo-alt.svg')->width(32)->height(32)))
                ->addMarker(new Marker(position: new Point(45.7640, 4.8357), title: 'Lyon', icon: Icon::ux('fa:map-marker')->width(32)->height(32)))
                ->addMarker(new Marker(position: new Point(45.8566, 2.3522), title: 'Dijon', icon: Icon::svg('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24">...</svg>'))),
        ];
    }
}
