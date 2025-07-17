<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Utils;

/**
 * Utility class to convert between decimal and DMS coordinates.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class CoordinateUtils
{
    /**
     * Converts a decimal coordinate to DMS (degrees, minutes, seconds).
     *
     * CoordinateUtils::decimalToDMS(48.8588443)
     *      --> [48, 51, 31.8388]
     *
     * @param float $decimal the decimal coordinate to convert to DMS
     *
     * @return array{0: int, 1: int, 2: float}
     */
    public static function decimalToDMS(float $decimal): array
    {
        $sign = $decimal < 0 ? -1 : 1;
        $decimal = abs($decimal);
        $degrees = (int) $decimal * $sign;
        $minutes = (int) (($decimal - abs($degrees)) * 60);
        $seconds = ($decimal - abs($degrees) - $minutes / 60) * 3600;

        return [$degrees, $minutes, round($seconds, 6)];
    }

    /**
     * Converts a DMS (degrees, minutes, seconds) coordinate to decimal.
     *
     *  CoordinateUtils::DMSToDecimal(48, 51, 31.8388)
     *      --> 48.8588443
     *
     * @param int   $degrees the degrees part of the DMS coordinate
     * @param int   $minutes the minutes part of the DMS coordinate
     * @param float $seconds the seconds part of the DMS coordinate
     *
     * @return float the decimal coordinate
     */
    public static function DMSToDecimal(int $degrees, int $minutes, float $seconds): float
    {
        $sign = $degrees < 0 ? -1 : 1;
        $degrees = abs($degrees);
        $decimal = $degrees + $minutes / 60 + $seconds / 3600;

        return round($decimal * $sign, 6);
    }
}
