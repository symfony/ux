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
 * Grid-based clustering algorithm for spatial data.
 *
 * This algorithm groups points into fixed-size grid cells based on the given zoom level.
 *
 * Best for:
 * - Fast, scalable clustering on large geographical datasets
 * - Real-time clustering where performance is critical
 * - Use cases where a simple, predictable grid structure is sufficient
 *
 * Slower for:
 * - Highly dynamic data that requires adaptive cluster sizes
 * - Scenarios where varying density should influence cluster sizes (e.g., DBSCAN-like approaches)
 * - Irregularly shaped clusters that do not fit a strict grid pattern
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class GridClusteringAlgorithm implements ClusteringAlgorithmInterface
{
    /**
     * Clusters a set of points using a fixed grid resolution based on the zoom level.
     *
     * @param Point[] $points List of points to be clustered
     * @param float   $zoom   The zoom level, determining grid resolution
     *
     * @return Cluster[] An array of clusters, each containing grouped points
     */
    public function cluster(iterable $points, float $zoom): array
    {
        $gridResolution = 1 << (int) $zoom;
        $gridSize = 360 / $gridResolution;
        $invGridSize = 1 / $gridSize;

        $cells = [];

        foreach ($points as $point) {
            $lng = $point->getLongitude();
            $lat = $point->getLatitude();
            $gridX = (int) (($lng + 180) * $invGridSize);
            $gridY = (int) (($lat + 90) * $invGridSize);
            $key = ($gridX << 16) | $gridY;

            if (!isset($cells[$key])) {
                $cells[$key] = new Cluster($point);
            } else {
                $cells[$key]->addPoint($point);
            }
        }

        return array_values($cells);
    }
}
