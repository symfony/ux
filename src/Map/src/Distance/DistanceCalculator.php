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
 * @author Simon André <smn.andre@gmail.com>
 */
final class DistanceCalculator implements DistanceCalculatorInterface
{
    public function __construct(
        private readonly DistanceCalculatorInterface $calculator = new VincentyDistanceCalculator(),
        private readonly DistanceUnit $unit = DistanceUnit::Meter,
    ) {
    }

    public function calculateDistance(Point $point1, Point $point2): float
    {
        return $this->calculator->calculateDistance($point1, $point2)
            * $this->unit->getConversionFactor();
    }
}
