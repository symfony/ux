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
use Symfony\UX\Map\Rectangle;

class RectangleTest extends TestCase
{
    public function testToArray()
    {
        $infoWindow = new InfoWindow('Hello');

        $southWest = new Point(1.0, 2.0);
        $northEast = new Point(3.0, 4.0);

        $rectangle = new Rectangle($southWest, $northEast, null, $infoWindow, ['foo' => 'bar'], 'rect1');

        $array = $rectangle->toArray();
        self::assertSame([
            'southWest' => ['lat' => 1.0, 'lng' => 2.0],
            'northEast' => ['lat' => 3.0, 'lng' => 4.0],
            'title' => null,
            'infoWindow' => $infoWindow->toArray(),
            'extra' => ['foo' => 'bar'],
            'id' => 'rect1',
        ], $array);
    }

    public function testFromArray()
    {
        $data = [
            'southWest' => ['lat' => 1.0, 'lng' => 2.0],
            'northEast' => ['lat' => 3.0, 'lng' => 4.0],
            'title' => null,
            'infoWindow' => ['content' => 'Hello'],
            'extra' => ['foo' => 'bar'],
            'id' => 'rect1',
        ];

        $rectangle = Rectangle::fromArray($data);

        $array = $rectangle->toArray();
        self::assertSame([
            'southWest' => ['lat' => 1.0, 'lng' => 2.0],
            'northEast' => ['lat' => 3.0, 'lng' => 4.0],
            'title' => null,
            'infoWindow' => [
                'headerContent' => null,
                'content' => 'Hello',
                'position' => null,
                'opened' => false,
                'autoClose' => true,
                'extra' => $array['infoWindow']['extra'],
            ],
            'extra' => ['foo' => 'bar'],
            'id' => 'rect1',
        ], $array);
    }

    public function testFromArrayThrowsExceptionIfSouthWestMissing()
    {
        $this->expectException(InvalidArgumentException::class);

        Rectangle::fromArray([
            'northEast' => ['lat' => 3.0, 'lng' => 4.0],
        ]);
    }

    public function testFromArrayThrowsExceptionIfNorthEastMissing()
    {
        $this->expectException(InvalidArgumentException::class);

        Rectangle::fromArray([
            'southWest' => ['lat' => 1.0, 'lng' => 2.0],
        ]);
    }
}
