<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Bridge\Leaflet\Tests\Option;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Map\Bridge\Leaflet\Option\TileLayer;

class TileLayerTest extends TestCase
{
    public function testToArray()
    {
        $tileLayer = new TileLayer(
            url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
            attribution: '© OpenStreetMap contributors',
            options: [
                'maxZoom' => 19,
            ],
        );

        $array = $tileLayer->toArray();

        self::assertSame([
            'url' => 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
            'attribution' => '© OpenStreetMap contributors',
            'options' => $array['options'], // stdClass
        ], $array);
        self::assertSame(19, $array['options']->maxZoom);
    }
}
