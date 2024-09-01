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
use Symfony\UX\Map\Map;

class MapFactoryTest extends TestCase
{
    public function testFromArray(): void
    {
        $array = self::createMapArray();
        $map = Map::fromArray($array);

        $this->assertEquals($array['center']['lat'], $map->toArray()['center']['lat']);
        $this->assertEquals($array['center']['lng'], $map->toArray()['center']['lng']);

        $this->assertEquals((float) $array['zoom'], $map->toArray()['zoom']);

        $this->assertCount(1, $markers = $map->toArray()['markers']);
        $this->assertEquals($array['markers'][0]['position']['lat'], $markers[0]['position']['lat']);
        $this->assertEquals($array['markers'][0]['position']['lng'], $markers[0]['position']['lng']);
        $this->assertSame($array['markers'][0]['title'], $markers[0]['title']);
        $this->assertSame($array['markers'][0]['infoWindow']['headerContent'], $markers[0]['infoWindow']['headerContent']);
        $this->assertSame($array['markers'][0]['infoWindow']['content'], $markers[0]['infoWindow']['content']);
    }

    public function testFromArrayWithInvalidCenter(): void
    {
        $array = self::createMapArray();
        $array['center'] = 'invalid';

        $this->expectException(\TypeError::class);
        Map::fromArray($array);
    }

    public function testFromArrayWithInvalidZoom(): void
    {
        $array = self::createMapArray();
        $array['zoom'] = 'invalid';

        $this->expectException(\TypeError::class);
        Map::fromArray($array);
    }

    public function testFromArrayWithInvalidMarkers(): void
    {
        $array = self::createMapArray();
        $array['markers'] = 'invalid';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "markers" parameter must be an array.');
        Map::fromArray($array);
    }

    public function testFromArrayWithInvalidMarker(): void
    {
        $array = self::createMapArray();
        $array['markers'] = [
            [
                'invalid',
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "position" parameter is required.');
        Map::fromArray($array);
    }

    private static function createMapArray(): array
    {
        return [
            'center' => [
                'lat' => 48.8566,
                'lng' => 2.3522,
            ],
            'zoom' => 12,
            'markers' => [
                [
                    'position' => [
                        'lat' => 48.8566,
                        'lng' => 2.3522,
                    ],
                    'title' => 'Paris',
                    'infoWindow' => [
                        'headerContent' => 'Paris',
                        'content' => 'Paris, the city of lights',
                    ],
                ],
            ],
        ];
    }
}
