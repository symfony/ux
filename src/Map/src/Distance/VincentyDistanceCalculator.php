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
 * Vincenty formula-based distance calculator.
 *
 * This calculator is more accurate than the Haversine formula, but slower.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class VincentyDistanceCalculator implements DistanceCalculatorInterface
{
    /**
     * WS-84 ellipsoid parameters.
     */
    // Major Axis in meters
    private const A = 6378137.0;
    // Flattening
    private const F = 1 / 298.257223563;
    // Minor Axis in meters
    private const B = 6356752.314245;

    public function calculateDistance(Point $point1, Point $point2): float
    {
        $phi1 = deg2rad($point1->getLatitude());
        $phi2 = deg2rad($point2->getLatitude());
        $lambda1 = deg2rad($point1->getLongitude());
        $lambda2 = deg2rad($point2->getLongitude());

        $L = $lambda2 - $lambda1;
        $U1 = atan((1 - self::F) * tan($phi1));
        $U2 = atan((1 - self::F) * tan($phi2));
        $sinU1 = sin($U1);
        $cosU1 = cos($U1);
        $sinU2 = sin($U2);
        $cosU2 = cos($U2);

        $lambda = $L;
        $iterLimit = 100;
        do {
            $sinLambda = sin($lambda);
            $cosLambda = cos($lambda);
            $sinSigma = sqrt(($cosU2 * $sinLambda) ** 2
                + ($cosU1 * $sinU2 - $sinU1 * $cosU2 * $cosLambda) ** 2);

            if (0.0 === $sinSigma) {
                return 0.0;
            }

            $cosSigma = $sinU1 * $sinU2 + $cosU1 * $cosU2 * $cosLambda;
            $sigma = atan2($sinSigma, $cosSigma);
            $sinAlpha = $cosU1 * $cosU2 * $sinLambda / $sinSigma;
            $cosSqAlpha = 1 - $sinAlpha * $sinAlpha;
            $cos2SigmaM = (0.0 === $cosSqAlpha) ? 0.0 : $cosSigma - 2 * $sinU1 * $sinU2 / $cosSqAlpha;
            $C = self::F / 16 * $cosSqAlpha * (4 + self::F * (4 - 3 * $cosSqAlpha));

            $lambdaPrev = $lambda;
            $lambda = $L + (1 - $C) * self::F * $sinAlpha
                * ($sigma + $C * $sinSigma * ($cos2SigmaM + $C * $cosSigma * (-1 + 2 * $cos2SigmaM * $cos2SigmaM)));
        } while (abs($lambda - $lambdaPrev) > 1e-12 && --$iterLimit > 0);

        if (0 === $iterLimit) {
            throw new \RuntimeException('Vincenty formula failed to converge.');
        }

        $uSq = $cosSqAlpha * (self::A * self::A - self::B * self::B) / (self::B * self::B);
        $Acoeff = 1 + $uSq / 16384 * (4096 + $uSq * (-768 + $uSq * (320 - 175 * $uSq)));
        $Bcoeff = $uSq / 1024 * (256 + $uSq * (-128 + $uSq * (74 - 47 * $uSq)));
        $deltaSigma = $Bcoeff * $sinSigma * ($cos2SigmaM + $Bcoeff / 4 * ($cosSigma * (-1 + 2 * $cos2SigmaM * $cos2SigmaM)
                    - $Bcoeff / 6 * $cos2SigmaM * (-3 + 4 * $sinSigma * $sinSigma) * (-3 + 4 * $cos2SigmaM * $cos2SigmaM)));
        $distance = self::B * $Acoeff * ($sigma - $deltaSigma);

        return $distance;
    }
}
