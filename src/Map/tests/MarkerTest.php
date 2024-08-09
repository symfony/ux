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
use Symfony\UX\Map\InfoWindow;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;

class MarkerTest extends TestCase
{
    public function testToArray(): void
    {
        $marker = new Marker(
            position: new Point(48.8566, 2.3522),
        );

        $array = $marker->toArray();

        self::assertSame([
            'position' => ['lat' => 48.8566, 'lng' => 2.3522],
            'title' => null,
            'infoWindow' => null,
            'extra' => $array['extra'],
        ], $array);

        $marker = new Marker(
            position: new Point(48.8566, 2.3522),
            title: 'Paris',
            infoWindow: new InfoWindow(
                headerContent: '<b>Paris</b>',
                content: "Capitale de la France, est une grande ville européenne et un centre mondial de l'art, de la mode, de la gastronomie et de la culture.",
                opened: true,
            ),
        );

        $array = $marker->toArray();

        self::assertSame([
            'position' => ['lat' => 48.8566, 'lng' => 2.3522],
            'title' => 'Paris',
            'infoWindow' => [
                'headerContent' => '<b>Paris</b>',
                'content' => "Capitale de la France, est une grande ville européenne et un centre mondial de l'art, de la mode, de la gastronomie et de la culture.",
                'position' => null,
                'opened' => true,
                'autoClose' => true,
                'extra' => $array['infoWindow']['extra'],
            ],
            'extra' => $array['extra'],
        ], $array);
    }
}
