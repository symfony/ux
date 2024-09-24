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
use Symfony\UX\Map\MapOptionsInterface;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;
use Symfony\UX\Map\Polygon;

class MapTest extends TestCase
{
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
        ], $array);
    }

    public function testWithMaximumConfiguration(): void
    {
        $map = new Map();
        $map
            ->center(new Point(48.8566, 2.3522))
            ->zoom(6)
            ->fitBoundsToMarkers()
            ->options(new class implements MapOptionsInterface {
                public function toArray(): array
                {
                    return [
                        'mapTypeId' => 'roadmap',
                    ];
                }
            })
            ->addMarker(new Marker(
                position: new Point(48.8566, 2.3522),
                title: 'Paris',
                infoWindow: new InfoWindow(headerContent: '<b>Paris</b>', content: 'Paris', position: new Point(48.8566, 2.3522))
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
        ;

        $array = $map->toArray();

        self::assertEquals([
            'center' => ['lat' => 48.8566, 'lng' => 2.3522],
            'zoom' => 6.0,
            'fitBoundsToMarkers' => true,
            'options' => $array['options'],
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
                        'extra' => $array['markers'][0]['infoWindow']['extra'],
                    ],
                    'extra' => $array['markers'][0]['extra'],
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
                        'extra' => $array['markers'][1]['infoWindow']['extra'],
                    ],
                    'extra' => $array['markers'][1]['extra'],
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
                        'extra' => $array['markers'][2]['infoWindow']['extra'],
                    ],
                    'extra' => $array['markers'][2]['extra'],
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
                    'extra' => $array['polygons'][0]['extra'],
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
                        'extra' => $array['polygons'][1]['infoWindow']['extra'],
                    ],
                    'extra' => $array['polygons'][1]['extra'],
                ],
            ],
        ], $array);

        self::assertSame('roadmap', $array['options']->mapTypeId);
    }
}
