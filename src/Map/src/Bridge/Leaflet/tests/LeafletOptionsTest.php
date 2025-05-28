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

        self::assertSame([
            'tileLayer' => [
                'url' => 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                'attribution' => '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                'options' => [],
            ],
        ], $leafletOptions->toArray());

        self::assertEquals($leafletOptions, LeafletOptions::fromArray($leafletOptions->toArray()));
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

        self::assertSame([
            'tileLayer' => [
                'url' => 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                'attribution' => '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                'options' => [
                    'maxZoom' => 19,
                    'minZoom' => 1,
                    'maxNativeZoom' => 18,
                    'zoomOffset' => 0,
                ],
            ],
        ], $leafletOptions->toArray());

        self::assertEquals($leafletOptions, LeafletOptions::fromArray($leafletOptions->toArray()));
    }

    public function testWithTileLayerFalse(): void
    {
        $leafletOptions = new LeafletOptions(tileLayer: false);

        self::assertSame([
            'tileLayer' => false,
        ], $leafletOptions->toArray());

        self::assertEquals($leafletOptions, LeafletOptions::fromArray($leafletOptions->toArray()));
    }
}
