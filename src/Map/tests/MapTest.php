<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Map\Exception\InvalidArgumentException;
use Symfony\UX\Map\InfoWindow;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;
use Symfony\UX\Map\Polygon;
use Symfony\UX\Map\Polyline;

class MapTest extends TestCase
{
    protected function setUp(): void
    {
        DummyOptions::registerToNormalizer();
    }

    protected function tearDown(): void
    {
        DummyOptions::unregisterFromNormalizer();
    }

    public function testCenterValidation(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('The map "center" must be explicitly set when not enabling "fitBoundsToMarkers" feature.');

        $map = new Map();
        $map->toArray();
    }

    public function testZoomValidation(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('The map "zoom" must be explicitly set when not enabling "fitBoundsToMarkers" feature.');

        $map = new Map(
            center: new Point(48.8566, 2.3522)
        );
        $map->toArray();
    }

    public function testZoomAndCenterCanBeOmittedIfFitBoundsToMarkers(): void
    {
        $map = new Map(
            fitBoundsToMarkers: true
        );

        $array = $map->toArray();

        self::assertSame([
            'center' => null,
            'zoom' => null,
            'fitBoundsToMarkers' => true,
            'options' => $array['options'],
            'markers' => [],
            'polygons' => [],
            'polylines' => [],
        ], $array);
    }

    public function testWithMinimumConfiguration(): void
    {
        $map = new Map();
        $map
            ->center(new Point(48.8566, 2.3522))
            ->zoom(6);

        $array = $map->toArray();

        self::assertSame([
            'center' => ['lat' => 48.8566, 'lng' => 2.3522],
            'zoom' => 6.0,
            'fitBoundsToMarkers' => false,
            'options' => $array['options'],
            'markers' => [],
            'polygons' => [],
            'polylines' => [],
        ], $array);
    }

    public function testWithMaximumConfiguration(): void
    {
        $map = new Map();
        $map
            ->center(new Point(48.8566, 2.3522))
            ->zoom(6)
            ->fitBoundsToMarkers()
            ->options(new DummyOptions(mapId: '1a2b3c4d5e', mapType: 'roadmap'))
            ->addMarker(new Marker(
                position: new Point(48.8566, 2.3522),
                title: 'Paris',
                infoWindow: new InfoWindow(headerContent: '<b>Paris</b>', content: 'Paris', position: new Point(48.8566, 2.3522), extra: ['baz' => 'qux']),
                extra: ['foo' => 'bar'],
            ))
            ->addMarker(new Marker(
                position: new Point(45.764, 4.8357),
                title: 'Lyon',
                infoWindow: new InfoWindow(headerContent: '<b>Lyon</b>', content: 'Lyon', position: new Point(45.764, 4.8357), opened: true)
            ))
            ->addMarker(new Marker(
                position: new Point(43.2965, 5.3698),
                title: 'Marseille',
                infoWindow: new InfoWindow(headerContent: '<b>Marseille</b>', content: 'Marseille', position: new Point(43.2965, 5.3698), opened: true)
            ))
            ->addPolygon(new Polygon(
                points: [
                    new Point(48.858844, 2.294351),
                    new Point(48.853, 2.3499),
                    new Point(48.8566, 2.3522),
                ],
                title: 'Polygon 1',
                infoWindow: null,
            ))
            ->addPolygon(new Polygon(
                points: [
                    new Point(45.764043, 4.835659),
                    new Point(45.75, 4.85),
                    new Point(45.77, 4.82),
                ],
                title: 'Polygon 2',
                infoWindow: new InfoWindow(
                    headerContent: '<b>Polygon 2</b>',
                    content: 'A polygon around Lyon with some additional info.',
                    position: new Point(45.764, 4.8357),
                    opened: true,
                    autoClose: true,
                ),
            ))
            ->addPolyline(new Polyline(
                points: [
                    new Point(48.858844, 2.294351),
                    new Point(48.853, 2.3499),
                    new Point(48.8566, 2.3522),
                ],
                title: 'Polyline 1',
                infoWindow: null,
            ))
            ->addPolyline(new Polyline(
                points: [
                    new Point(45.764043, 4.835659),
                    new Point(45.75, 4.85),
                    new Point(45.77, 4.82),
                ],
                title: 'Polyline 2',
                infoWindow: new InfoWindow(
                    headerContent: '<b>Polyline 2</b>',
                    content: 'A polyline around Lyon with some additional info.',
                    position: new Point(45.764, 4.8357),
                    opened: true,
                    autoClose: true,
                ),
            ))
        ;

        self::assertEquals([
            'center' => ['lat' => 48.8566, 'lng' => 2.3522],
            'zoom' => 6.0,
            'fitBoundsToMarkers' => true,
            'options' => [
                '@provider' => 'dummy',
                'mapId' => '1a2b3c4d5e',
                'mapType' => 'roadmap',
            ],
            'markers' => [
                [
                    'position' => ['lat' => 48.8566, 'lng' => 2.3522],
                    'title' => 'Paris',
                    'infoWindow' => [
                        'headerContent' => '<b>Paris</b>',
                        'content' => 'Paris',
                        'position' => ['lat' => 48.8566, 'lng' => 2.3522],
                        'opened' => false,
                        'autoClose' => true,
                        'extra' => ['baz' => 'qux'],
                    ],
                    'icon' => null,
                    'extra' => ['foo' => 'bar'],
                    'id' => null,
                ],
                [
                    'position' => ['lat' => 45.764, 'lng' => 4.8357],
                    'title' => 'Lyon',
                    'infoWindow' => [
                        'headerContent' => '<b>Lyon</b>',
                        'content' => 'Lyon',
                        'position' => ['lat' => 45.764, 'lng' => 4.8357],
                        'opened' => true,
                        'autoClose' => true,
                        'extra' => [],
                    ],
                    'icon' => null,
                    'extra' => [],
                    'id' => null,
                ],
                [
                    'position' => ['lat' => 43.2965, 'lng' => 5.3698],
                    'title' => 'Marseille',
                    'infoWindow' => [
                        'headerContent' => '<b>Marseille</b>',
                        'content' => 'Marseille',
                        'position' => ['lat' => 43.2965, 'lng' => 5.3698],
                        'opened' => true,
                        'autoClose' => true,
                        'extra' => [],
                    ],
                    'icon' => null,
                    'extra' => [],
                    'id' => null,
                ],
            ],
            'polygons' => [
                [
                    'points' => [
                        ['lat' => 48.858844, 'lng' => 2.294351],
                        ['lat' => 48.853, 'lng' => 2.3499],
                        ['lat' => 48.8566, 'lng' => 2.3522],
                    ],
                    'title' => 'Polygon 1',
                    'infoWindow' => null,
                    'extra' => [],
                    'id' => null,
                ],
                [
                    'points' => [
                        ['lat' => 45.764043, 'lng' => 4.835659],
                        ['lat' => 45.75, 'lng' => 4.85],
                        ['lat' => 45.77, 'lng' => 4.82],
                    ],
                    'title' => 'Polygon 2',
                    'infoWindow' => [
                        'headerContent' => '<b>Polygon 2</b>',
                        'content' => 'A polygon around Lyon with some additional info.',
                        'position' => ['lat' => 45.764, 'lng' => 4.8357],
                        'opened' => true,
                        'autoClose' => true,
                        'extra' => [],
                    ],
                    'extra' => [],
                    'id' => null,
                ],
            ],
            'polylines' => [
                [
                    'points' => [
                        ['lat' => 48.858844, 'lng' => 2.294351],
                        ['lat' => 48.853, 'lng' => 2.3499],
                        ['lat' => 48.8566, 'lng' => 2.3522],
                    ],
                    'title' => 'Polyline 1',
                    'infoWindow' => null,
                    'extra' => [],
                    'id' => null,
                ],
                [
                    'points' => [
                        ['lat' => 45.764043, 'lng' => 4.835659],
                        ['lat' => 45.75, 'lng' => 4.85],
                        ['lat' => 45.77, 'lng' => 4.82],
                    ],
                    'title' => 'Polyline 2',
                    'infoWindow' => [
                        'headerContent' => '<b>Polyline 2</b>',
                        'content' => 'A polyline around Lyon with some additional info.',
                        'position' => ['lat' => 45.764, 'lng' => 4.8357],
                        'opened' => true,
                        'autoClose' => true,
                        'extra' => [],
                    ],
                    'extra' => [],
                    'id' => null,
                ],
            ],
        ], $map->toArray());
    }
}
