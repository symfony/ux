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

class MapTest extends TestCase
{
    public function testCenterValidation(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('The center of the map must be set.');

        $map = new Map();
        $map->toArray();
    }

    public function testZoomValidation(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('The zoom of the map must be set.');

        $map = new Map(
            center: new Point(48.8566, 2.3522)
        );
        $map->toArray();
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
        ], $array);
    }

    public function testWithMaximumConfiguration(): void
    {
        $map = new Map();
        $map
            ->center(new Point(48.8566, 2.3522))
            ->zoom(6)
            ->fitBoundsToMarkers()
            ->options(new class() implements MapOptionsInterface {
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
            ));

        $array = $map->toArray();

        self::assertSame([
            'center' => ['lat' => 48.8566, 'lng' => 2.3522],
            'zoom' => 6.0,
            'fitBoundsToMarkers' => true,
            'options' => $array['options'],
            'markers' => [
                [
                    'position' => ['lat' => 48.8566, 'lng' => 2.3522],
                    'title' => 'Paris',
                    'infoWindow' => ['headerContent' => '<b>Paris</b>', 'content' => 'Paris', 'position' => ['lat' => 48.8566, 'lng' => 2.3522], 'opened' => false, 'autoClose' => true],
                ],
                [
                    'position' => ['lat' => 45.764, 'lng' => 4.8357],
                    'title' => 'Lyon',
                    'infoWindow' => ['headerContent' => '<b>Lyon</b>', 'content' => 'Lyon', 'position' => ['lat' => 45.764, 'lng' => 4.8357], 'opened' => true, 'autoClose' => true],
                ],
                [
                    'position' => ['lat' => 43.2965, 'lng' => 5.3698],
                    'title' => 'Marseille',
                    'infoWindow' => ['headerContent' => '<b>Marseille</b>', 'content' => 'Marseille', 'position' => ['lat' => 43.2965, 'lng' => 5.3698], 'opened' => true, 'autoClose' => true],
                ],
            ],
        ], $array);

        self::assertSame('roadmap', $array['options']->mapTypeId);
    }
}
