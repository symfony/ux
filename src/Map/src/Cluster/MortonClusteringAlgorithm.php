<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Cluster;

use Symfony\UX\Map\Point;

/**
 * Clustering algorithm based on Morton codes (Z-order curves).
 *
 * This approach is optimized for spatial data and preserves locality efficiently.
 *
 * Best for:
 * - Large-scale spatial clustering
 * - Hierarchical clustering with fast locality-based grouping
 * - Datasets where preserving spatial proximity is crucial
 *
 * Slower for:
 * - High-dimensional data (beyond 2D/3D) due to Morton code limitations
 * - Non-spatial or categorical data
 * - Scenarios requiring dynamic cluster adjustments (e.g., streaming data)
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class MortonClusteringAlgorithm implements ClusteringAlgorithmInterface
{
    /**
     * @param Point[] $points
     *
     * @return Cluster[]
     */
    public function cluster(iterable $points, float $zoom): array
    {
        $resolution = 1 << (int) $zoom;
        $clustersMap = [];

        foreach ($points as $point) {
            $xNorm = ($point->getLatitude() + 180) / 360;
            $yNorm = ($point->getLongitude() + 90) / 180;

            $x = (int) floor($xNorm * $resolution);
            $y = (int) floor($yNorm * $resolution);

            $x &= 0xFFFF;
            $y &= 0xFFFF;

            $x = ($x | ($x << 8)) & 0x00FF00FF;
            $x = ($x | ($x << 4)) & 0x0F0F0F0F;
            $x = ($x | ($x << 2)) & 0x33333333;
            $x = ($x | ($x << 1)) & 0x55555555;

            $y = ($y | ($y << 8)) & 0x00FF00FF;
            $y = ($y | ($y << 4)) & 0x0F0F0F0F;
            $y = ($y | ($y << 2)) & 0x33333333;
            $y = ($y | ($y << 1)) & 0x55555555;

            $code = ($y << 1) | $x;

            if (!isset($clustersMap[$code])) {
                $clustersMap[$code] = new Cluster($point);
            } else {
                $clustersMap[$code]->addPoint($point);
            }
        }

        return array_values($clustersMap);
    }
}
