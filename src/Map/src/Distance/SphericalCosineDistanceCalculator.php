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
 * Sphere-based distance calculator using the cosine of the spherical distance.
 *
 * This calculator is faster than the Haversine formula, but less accurate.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class SphericalCosineDistanceCalculator implements DistanceCalculatorInterface
{
    /**
     * @var float the Earth's radius in meters
     */
    private const EARTH_RADIUS = 6371000.0;

    public function calculateDistance(Point $point1, Point $point2): float
    {
        $lat1Rad = deg2rad($point1->getLatitude());
        $lat2Rad = deg2rad($point2->getLatitude());
        $lng1Rad = deg2rad($point1->getLongitude());
        $lng2Rad = deg2rad($point2->getLongitude());

        $cosDistance = sin($lat1Rad) * sin($lat2Rad) + cos($lat1Rad) * cos($lat2Rad) * cos($lng2Rad - $lng1Rad);

        // Correct for floating-point errors.
        $cosDistance = min(1.0, max(-1.0, $cosDistance));
        $angle = acos($cosDistance);

        return self::EARTH_RADIUS * $angle;
    }
}
