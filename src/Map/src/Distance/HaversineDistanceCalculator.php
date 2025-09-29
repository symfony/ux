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

use Symfony\UX\Map\Point;

/**
 * Haversine formula-based distance calculator.
 *
 * This calculator is accurate but slower than the spherical cosine formula.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class HaversineDistanceCalculator implements DistanceCalculatorInterface
{
    /**
     * @var float the Earth's radius in meters
     */
    private const EARTH_RADIUS = 6371000.0;

    public function calculateDistance(Point $point1, Point $point2): float
    {
        $lat1Rad = deg2rad($point1->getLatitude());
        $lat2Rad = deg2rad($point2->getLatitude());
        $deltaLat = deg2rad($point2->getLatitude() - $point1->getLatitude());
        $deltaLng = deg2rad($point2->getLongitude() - $point1->getLongitude());

        $a = sin($deltaLat / 2) ** 2 + cos($lat1Rad) * cos($lat2Rad) * sin($deltaLng / 2) ** 2;
        $c = 2 * asin(min(1.0, sqrt($a)));

        return self::EARTH_RADIUS * $c;
    }
}
