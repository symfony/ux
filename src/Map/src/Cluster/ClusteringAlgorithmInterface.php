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
 * Interface for various Clustering implementations.
 */
interface ClusteringAlgorithmInterface
{
    /**
     * Clusters a set of points.
     *
     * @param Point[] $points List of points to be clustered
     * @param float   $zoom   The zoom level, determining grid resolution
     *
     * @return Cluster[] An array of clusters, each containing grouped points
     */
    public function cluster(array $points, float $zoom): array;
}
