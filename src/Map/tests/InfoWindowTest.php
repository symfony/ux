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
use Symfony\UX\Map\Point;

class InfoWindowTest extends TestCase
{
    public function testToArray(): void
    {
        $infoWindow = new InfoWindow(
            headerContent: 'Paris',
            content: 'Capitale de la France, est une grande ville européenne et un centre mondial de l\'art, de la mode, de la gastronomie et de la culture.',
            position: new Point(48.8566, 2.3522),
            opened: true,
            autoClose: false,
        );

        $array = $infoWindow->toArray();

        self::assertSame([
            'headerContent' => 'Paris',
            'content' => 'Capitale de la France, est une grande ville européenne et un centre mondial de l\'art, de la mode, de la gastronomie et de la culture.',
            'position' => [
                'lat' => 48.8566,
                'lng' => 2.3522,
            ],
            'opened' => true,
            'autoClose' => false,
            'extra' => $array['extra'],
        ], $array);
    }
}
