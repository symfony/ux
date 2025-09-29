<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Tests\Cluster;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Map\Cluster\Cluster;
use Symfony\UX\Map\Cluster\GridClusteringAlgorithm;
use Symfony\UX\Map\Point;

class GridClusteringAlgorithmTest extends TestCase
{
    public function testSinglePointCreatesSingleCluster(): void
    {
        $point = new Point(10.0, 20.0);
        $algorithm = new GridClusteringAlgorithm();
        $clusters = $algorithm->cluster([$point], 1.0);

        $this->assertCount(1, $clusters);

        /** @var Cluster $cluster */
        $cluster = $clusters[0];

        $this->assertEquals(10.0, $cluster->getCenter()->getLatitude());
        $this->assertEquals(20.0, $cluster->getCenter()->getLongitude());
        $this->assertCount(1, $cluster->getPoints());
    }

    public function testPointsInSameGridAreClusteredTogether(): void
    {
        $point1 = new Point(10.0, 20.0);
        $point2 = new Point(10.1, 20.1);
        $algorithm = new GridClusteringAlgorithm();

        $clusters = $algorithm->cluster([$point1, $point2], 1.0);

        $this->assertCount(1, $clusters, 'One cluster should have been created due to the low zoom value.');

        $cluster = $clusters[0];

        $this->assertCount(2, $cluster->getPoints());
        $this->assertEqualsWithDelta(10.05, $cluster->getCenter()->getLatitude(), 0.0001);
        $this->assertEqualsWithDelta(20.05, $cluster->getCenter()->getLongitude(), 0.0001);
    }

    public function testPointsInDifferentGridsAreNotClustered(): void
    {
        $point1 = new Point(10.0, 20.0);
        $point2 = new Point(-10.0, -20.0); // Far away
        $algorithm = new GridClusteringAlgorithm();

        $clusters = $algorithm->cluster([$point1, $point2], 5.0);

        $this->assertCount(2, $clusters, 'Two clusters should have created due to the high zoom value.');
    }

    public function testEmptyPointsArray(): void
    {
        $algorithm = new GridClusteringAlgorithm();

        // Empty points array
        $clusters = $algorithm->cluster([], 2.0);

        $this->assertCount(0, $clusters);
    }

    public function testLargeCoordinates(): void
    {
        $point1 = new Point(89.9, 179.9);
        $point2 = new Point(-89.9, -179.9);
        $algorithm = new GridClusteringAlgorithm();

        $clusters = $algorithm->cluster([$point1, $point2], 3.0);

        $this->assertGreaterThanOrEqual(1, \count($clusters));
    }

    public function testZeroZoomLevel(): void
    {
        $point1 = new Point(10, 20);
        $point2 = new Point(30, 40);
        $algorithm = new GridClusteringAlgorithm();

        // With zoom 0, everything should be in one big cluster.
        $clusters = $algorithm->cluster([$point1, $point2], 0.0);

        $this->assertCount(1, $clusters);
        $this->assertCount(2, $clusters[0]->getPoints());
    }
}
