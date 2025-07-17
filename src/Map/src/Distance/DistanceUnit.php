<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Distance;

/**
 * Represents a distance unit used in mapping and geospatial calculations.
 *
 * This enum defines common units for measuring distances. Each unit has an associated
 * conversion factor which is used to convert a value from meters to that unit.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
enum DistanceUnit: string
{
    /**
     * The "meter" unit.
     *
     * This is the International System of Units (SI) base unit for length.
     */
    case Meter = 'm';

    /**
     * The "kilometer" unit.
     *
     * This unit is commonly used for longer distances.
     */
    case Kilometer = 'km';

    /**
     * The "mile" unit.
     *
     * This unit is widely used in the United States.
     */
    case Mile = 'mi';

    /**
     * The "nautical mile" unit.
     *
     * This unit is typically used in navigation.
     */
    case NauticalMile = 'nmi';

    /**
     * Returns the conversion factor to convert this unit to meters.
     */
    public function getConversionFactor(): float
    {
        return match ($this) {
            self::Meter => 1.0,
            self::Kilometer => 0.001,
            self::Mile => 0.000621371,
            self::NauticalMile => 0.000539957,
        };
    }

    /**
     * Returns the conversion factor to convert this unit to another unit.
     */
    public function getConversionFactorTo(DistanceUnit $unit): float
    {
        return $this->getConversionFactor() / $unit->getConversionFactor();
    }

    /**
     * Returns the conversion factor to convert another unit to this unit.
     */
    public function getConversionFactorFrom(DistanceUnit $unit): float
    {
        return $unit->getConversionFactor() / $this->getConversionFactor();
    }
}
