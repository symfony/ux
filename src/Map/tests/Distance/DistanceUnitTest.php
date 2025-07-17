<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Tests\Distance;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Map\Distance\DistanceUnit;

class DistanceUnitTest extends TestCase
{
    public function testConversionFactorIsPositive()
    {
        foreach (DistanceUnit::cases() as $unit) {
            $this->assertGreaterThan(0, $unit->getConversionFactor());
        }
    }

    public function testConversionFactorToMeterIsSameAsConversionFactor()
    {
        foreach (DistanceUnit::cases() as $unit) {
            $this->assertEquals($unit->getConversionFactor(), $unit->getConversionFactorTo(DistanceUnit::Meter));
        }
    }

    /**
     * @dataProvider provideConvertedUnits
     */
    public function testConversionFactorFrom(DistanceUnit $unit, DistanceUnit $otherUnit, float $expected): void
    {
        $this->assertEqualsWithDelta($expected, $unit->getConversionFactorFrom($otherUnit), 0.001);
    }

    public static function provideConvertedUnits(): iterable
    {
        yield 'Kilometer to Kilometer' => [DistanceUnit::Kilometer, DistanceUnit::Kilometer, 1.0];
        yield 'Kilometer to Meter' => [DistanceUnit::Kilometer, DistanceUnit::Meter, 1000.0];
        yield 'Kilometer to Mile' => [DistanceUnit::Kilometer, DistanceUnit::Mile, 0.621371];
        yield 'Kilometer to Nautical Mile' => [DistanceUnit::Kilometer, DistanceUnit::NauticalMile, 0.539957];

        yield 'Meter to Kilometer' => [DistanceUnit::Meter, DistanceUnit::Kilometer, 0.001];
        yield 'Meter to Meter' => [DistanceUnit::Meter, DistanceUnit::Meter, 1.0];
        yield 'Meter to Mile' => [DistanceUnit::Meter, DistanceUnit::Mile, 0.000621371];
        yield 'Meter to Nautical Mile' => [DistanceUnit::Meter, DistanceUnit::NauticalMile, 0.000539957];

        yield 'Mile to Kilometer' => [DistanceUnit::Mile, DistanceUnit::Kilometer, 1.609344];
        yield 'Mile to Meter' => [DistanceUnit::Mile, DistanceUnit::Meter, 1609.344];
        yield 'Mile to Mile' => [DistanceUnit::Mile, DistanceUnit::Mile, 1.0];
        yield 'Mile to Nautical Mile' => [DistanceUnit::Mile, DistanceUnit::NauticalMile, 0.868976];

        yield 'Nautical Mile to Kilometer' => [DistanceUnit::NauticalMile, DistanceUnit::Kilometer, 1.852];
        yield 'Nautical Mile to Meter' => [DistanceUnit::NauticalMile, DistanceUnit::Meter, 1852.0];
        yield 'Nautical Mile to Mile' => [DistanceUnit::NauticalMile, DistanceUnit::Mile, 1.15078];
        yield 'Nautical Mile to Nautical Mile' => [DistanceUnit::NauticalMile, DistanceUnit::NauticalMile, 1.0];
    }
}
