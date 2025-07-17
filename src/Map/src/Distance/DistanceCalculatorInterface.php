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
 * Interface for distance calculators.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
interface DistanceCalculatorInterface
{
    /**
     * Returns the distance between two points given their coordinates.
     *
     * @return float the distance between the two points, in meters
     */
    public function calculateDistance(Point $point1, Point $point2): float;
}
