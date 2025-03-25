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

use Symfony\UX\Icons\IconRendererInterface;
use Symfony\UX\Map\Bridge\Leaflet\Renderer\LeafletRenderer;
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

class LeafletRendererTest extends RendererTestCase
{
    public static function provideTestRenderMap(): iterable
    {
        $map = (new Map())
            ->center(new Point(48.8566, 2.3522))
            ->zoom(12);

        $marker1 = new Marker(position: new Point(48.8566, 2.3522), title: 'Paris', id: 'marker1');
        $marker2 = new Marker(position: new Point(48.8566, 2.3522), title: 'Lyon', infoWindow: new InfoWindow(content: 'Lyon'), id: 'marker2');
        $marker3 = new Marker(position: new Point(45.8566, 2.3522), title: 'Dijon', id: 'marker3');

        yield 'simple map' => [
            'renderer' => new LeafletRenderer(new StimulusHelper(null), new UxIconRenderer(null)),
            'map' => (clone $map),
        ];

        yield 'with custom attributes' => [
            'renderer' => new LeafletRenderer(new StimulusHelper(null), new UxIconRenderer(null)),
            'map' => (clone $map),
            'attributes' => ['data-controller' => 'my-custom-controller', 'class' => 'map'],
        ];

        yield 'with markers and infoWindows' => [
            'renderer' => new LeafletRenderer(new StimulusHelper(null), new UxIconRenderer(null)),
            'map' => (new Map())
                ->center(new Point(48.8566, 2.3522))
                ->zoom(12)
                ->addMarker($marker1)
                ->addMarker(new Marker(position: new Point(48.8566, 2.3522), title: 'Lyon', infoWindow: new InfoWindow(content: 'Lyon'))),
        ];

        yield 'with all markers removed' => [
            'renderer' => new LeafletRenderer(new StimulusHelper(null), new UxIconRenderer(null)),
            'map' => (new Map())
                ->center(new Point(48.8566, 2.3522))
                ->zoom(12)
                ->addMarker($marker1)
                ->addMarker($marker2)
                ->removeMarker($marker1)
                ->removeMarker($marker2),
        ];

        yield 'with marker remove and new ones added' => [
            'renderer' => new LeafletRenderer(new StimulusHelper(null), new UxIconRenderer(null)),
            'map' => (new Map())
                ->center(new Point(48.8566, 2.3522))
                ->zoom(12)
                ->addMarker($marker3)
                ->removeMarker($marker3)
                ->addMarker($marker1)
                ->addMarker($marker2),
        ];

        yield 'with polygons and infoWindows' => [
            'renderer' => new LeafletRenderer(new StimulusHelper(null), new UxIconRenderer(null)),
            'map' => (new Map())
                ->center(new Point(48.8566, 2.3522))
                ->zoom(12)
                ->addPolygon(new Polygon(points: [new Point(48.8566, 2.3522), new Point(48.8566, 2.3522), new Point(48.8566, 2.3522)], id: 'polygon1'))
                ->addPolygon(new Polygon(points: [new Point(1.1, 2.2), new Point(3.3, 4.4), new Point(5.5, 6.6)], infoWindow: new InfoWindow(content: 'Polygon'), id: 'polygon2')),
        ];

        yield 'with polylines and infoWindows' => [
            'renderer' => new LeafletRenderer(new StimulusHelper(null), new UxIconRenderer(null)),
            'map' => (new Map())
                ->center(new Point(48.8566, 2.3522))
                ->zoom(12)
                ->addPolyline(new Polyline(points: [new Point(48.8566, 2.3522), new Point(48.8566, 2.3522), new Point(48.8566, 2.3522)], id: 'polyline1'))
                ->addPolyline(new Polyline(points: [new Point(1.1, 2.2), new Point(3.3, 4.4), new Point(5.5, 6.6)], infoWindow: new InfoWindow(content: 'Polyline'), id: 'polyline2')),
        ];

        yield 'markers with icons' => [
            'renderer' => new LeafletRenderer(
                new StimulusHelper(null),
                new UxIconRenderer(new class implements IconRendererInterface {
                    public function renderIcon(string $name, array $attributes = []): string
                    {
                        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24">...</svg>';
                    }
                })),
            'map' => (new Map())
                ->center(new Point(48.8566, 2.3522))
                ->zoom(12)
                ->addMarker(new Marker(position: new Point(48.8566, 2.3522), title: 'Paris', icon: Icon::url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/icons/geo-alt.svg')->width(32)->height(32)))
                ->addMarker(new Marker(position: new Point(45.7640, 4.8357), title: 'Lyon', icon: Icon::ux('fa:map-marker')->width(32)->height(32)))
                ->addMarker(new Marker(position: new Point(45.8566, 2.3522), title: 'Dijon', icon: Icon::svg('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24">...</svg>'))),
        ];
    }
}
