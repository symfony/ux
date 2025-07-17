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
use Symfony\UX\Map\Point;
use Symfony\UX\Map\Polygon;

class PolygonTest extends TestCase
{
    public function testToArray()
    {
        $point1 = new Point(1.1, 2.2);
        $point2 = new Point(3.3, 4.4);

        $infoWindow = new InfoWindow('info content');

        $polygon = new Polygon(
            points: [$point1, $point2],
            title: 'Test Polygon',
            infoWindow: $infoWindow,
            extra: ['foo' => 'bar'],
            id: 'poly1'
        );

        $array = $polygon->toArray();
        $this->assertSame([
            'points' => [['lat' => 1.1, 'lng' => 2.2], ['lat' => 3.3, 'lng' => 4.4]],
            'title' => 'Test Polygon',
            'infoWindow' => [
                'headerContent' => 'info content',
                'content' => null,
                'position' => null,
                'opened' => false,
                'autoClose' => true,
                'extra' => $array['infoWindow']['extra'],
            ],
            'extra' => ['foo' => 'bar'],
            'id' => 'poly1',
        ], $array);
    }

    public function testToArrayMultidimensional()
    {
        $point1 = new Point(1.1, 2.2);
        $point2 = new Point(3.3, 4.4);
        $point3 = new Point(5.5, 6.6);

        $polygon = new Polygon(
            points: [[$point1, $point2], [$point3]],
        );

        $array = $polygon->toArray();
        $this->assertSame([
            'points' => [
                [['lat' => 1.1, 'lng' => 2.2], ['lat' => 3.3, 'lng' => 4.4]],
                [['lat' => 5.5, 'lng' => 6.6]],
            ],
            'title' => null,
            'infoWindow' => null,
            'extra' => $array['extra'],
            'id' => null,
        ], $array);
    }

    public function testFromArray()
    {
        $data = [
            'points' => [
                ['lat' => 1.1, 'lng' => 2.2], ['lat' => 3.3, 'lng' => 4.4],
            ],
            'title' => 'Test Polygon',
            'infoWindow' => ['content' => 'info content'],
            'extra' => ['foo' => 'bar'],
            'id' => 'poly1',
        ];

        $polygon = Polygon::fromArray($data);

        $this->assertInstanceOf(Polygon::class, $polygon);

        $array = $polygon->toArray();
        $this->assertSame([
            'points' => [['lat' => 1.1, 'lng' => 2.2], ['lat' => 3.3, 'lng' => 4.4]],
            'title' => 'Test Polygon',
            'infoWindow' => [
                'headerContent' => null,
                'content' => 'info content',
                'position' => null,
                'opened' => false,
                'autoClose' => true,
                'extra' => $array['infoWindow']['extra'],
            ],
            'extra' => ['foo' => 'bar'],
            'id' => 'poly1',
        ], $array);
    }

    public function testFromArrayMultidimensional()
    {
        $data = [
            'points' => [
                [['lat' => 1.1, 'lng' => 2.2], ['lat' => 3.3, 'lng' => 4.4]],
                [['lat' => 5.5, 'lng' => 6.6]],
            ],
            'title' => 'Test Polygon',
            'infoWindow' => ['content' => 'info content'],
            'extra' => ['foo' => 'bar'],
            'id' => 'poly1',
        ];

        $polygon = Polygon::fromArray($data);

        $this->assertInstanceOf(Polygon::class, $polygon);

        $array = $polygon->toArray();
        $this->assertSame([
            'points' => [
                [['lat' => 1.1, 'lng' => 2.2], ['lat' => 3.3, 'lng' => 4.4]],
                [['lat' => 5.5, 'lng' => 6.6]],
            ],
            'title' => 'Test Polygon',
            'infoWindow' => [
                'headerContent' => null,
                'content' => 'info content',
                'position' => null,
                'opened' => false,
                'autoClose' => true,
                'extra' => $array['infoWindow']['extra'],
            ],
            'extra' => ['foo' => 'bar'],
            'id' => 'poly1',
        ], $array);
    }

    public function testFromArrayThrowsExceptionIfPointsMissing()
    {
        $this->expectException(InvalidArgumentException::class);
        Polygon::fromArray(['invalid' => 'No points']);
    }
}
