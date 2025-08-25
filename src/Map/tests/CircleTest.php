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
use Symfony\UX\Map\Circle;
use Symfony\UX\Map\Exception\InvalidArgumentException;
use Symfony\UX\Map\InfoWindow;
use Symfony\UX\Map\Point;

class CircleTest extends TestCase
{
    public function testToArray()
    {
        $center = new Point(1.1, 2.2);
        $infoWindow = new InfoWindow('info content');

        $circle = new Circle(
            center: $center,
            radius: 500,
            infoWindow: $infoWindow,
            extra: ['foo' => 'bar'],
            id: 'circle1'
        );

        $array = $circle->toArray();
        self::assertSame([
            'center' => ['lat' => 1.1, 'lng' => 2.2],
            'radius' => 500.0,
            'infoWindow' => [
                'headerContent' => 'info content',
                'content' => null,
                'position' => null,
                'opened' => false,
                'autoClose' => true,
                'extra' => $array['infoWindow']['extra'],
            ],
            'extra' => ['foo' => 'bar'],
            'id' => 'circle1',
        ], $array);
    }

    public function testFromArray()
    {
        $data = [
            'center' => ['lat' => 1.1, 'lng' => 2.2],
            'radius' => 500,
            'infoWindow' => ['content' => 'info content'],
            'extra' => ['foo' => 'bar'],
            'id' => 'circle1',
        ];

        $circle = Circle::fromArray($data);

        self::assertInstanceOf(Circle::class, $circle);

        $array = $circle->toArray();
        self::assertSame([
            'center' => ['lat' => 1.1, 'lng' => 2.2],
            'radius' => 500.0,
            'infoWindow' => [
                'headerContent' => null,
                'content' => 'info content',
                'position' => null,
                'opened' => false,
                'autoClose' => true,
                'extra' => $array['infoWindow']['extra'],
            ],
            'extra' => ['foo' => 'bar'],
            'id' => 'circle1',
        ], $array);
    }

    public function testFromArrayThrowsExceptionIfCenterMissing()
    {
        $this->expectException(InvalidArgumentException::class);
        Circle::fromArray(['radius' => 500, 'invalid' => 'No center']);
    }

    public function testFromArrayThrowsExceptionIfRadiusMissing()
    {
        $this->expectException(InvalidArgumentException::class);
        Circle::fromArray(['center' => ['lat' => 1.1, 'lng' => 2.2], 'invalid' => 'No radius']);
    }

    public function testConstructorThrowsExceptionIfRadiusNotPositive()
    {
        $this->expectException(InvalidArgumentException::class);
        new Circle(new Point(1.1, 2.2), 0);
    }
}
