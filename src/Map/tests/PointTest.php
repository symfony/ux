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
use Symfony\UX\Map\Point;

class PointTest extends TestCase
{
    public static function provideInvalidPoint(): iterable
    {
        yield [91, 0, 'Latitude must be between -90 and 90 degrees, "91" given.'];
        yield [-91, 0, 'Latitude must be between -90 and 90 degrees, "-91" given.'];
        yield [0, 181, 'Longitude must be between -180 and 180 degrees, "181" given.'];
        yield [0, -181, 'Longitude must be between -180 and 180 degrees, "-181" given.'];
    }

    /**
     * @dataProvider provideInvalidPoint
     */
    public function testInvalidPoint(float $latitude, float $longitude, string $expectedExceptionMessage): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage($expectedExceptionMessage);

        new Point($latitude, $longitude);
    }

    public function testToArray(): void
    {
        $point = new Point(48.8566, 2.3533);

        self::assertSame(['lat' => 48.8566, 'lng' => 2.3533], $point->toArray());
    }
}
