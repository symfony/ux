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

use PHPUnit\Framework\TestCase;
use Symfony\UX\Map\Bridge\Leaflet\LeafletOptions;
use Symfony\UX\Map\Bridge\Leaflet\Option\TileLayer;

class LeafletOptionsTest extends TestCase
{
    public function testWithMinimalConfiguration(): void
    {
        $leafletOptions = new LeafletOptions();

        $array = $leafletOptions->toArray();

        self::assertSame([
            'tileLayer' => [
                'url' => 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                'attribution' => '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                'options' => $array['tileLayer']['options'], // stdClass
            ],
        ], $array);
    }

    public function testWithMaximumConfiguration(): void
    {
        $leafletOptions = new LeafletOptions(
            tileLayer: new TileLayer(
                url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                options: [
                    'maxZoom' => 19,
                    'minZoom' => 1,
                    'maxNativeZoom' => 18,
                    'zoomOffset' => 0,
                ],
            ),
        );

        $array = $leafletOptions->toArray();

        self::assertSame([
            'tileLayer' => [
                'url' => 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                'attribution' => '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                'options' => $array['tileLayer']['options'], // stdClass
            ],
        ], $array);
        self::assertSame(19, $array['tileLayer']['options']->maxZoom);
        self::assertSame(1, $array['tileLayer']['options']->minZoom);
        self::assertSame(18, $array['tileLayer']['options']->maxNativeZoom);
        self::assertSame(0, $array['tileLayer']['options']->zoomOffset);
    }
}
