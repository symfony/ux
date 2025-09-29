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
use Symfony\UX\Map\Cluster\MortonClusteringAlgorithm;
use Symfony\UX\Map\Point;

class MortonClusteringAlgorithmTest extends TestCase
{
    public function testSinglePointCreatesSingleCluster(): void
    {
        $point = new Point(10.0, 20.0);
        $algorithm = new MortonClusteringAlgorithm();
        $clusters = $algorithm->cluster([$point], 1.0);

        $this->assertCount(1, $clusters);

        /** @var Cluster $cluster */
        $cluster = $clusters[0];

        $this->assertEquals(10.0, $cluster->getCenter()->getLatitude());
        $this->assertEquals(20.0, $cluster->getCenter()->getLongitude());
        $this->assertCount(1, $cluster->getPoints());
    }

    public function testPointsWithSameMortonCodeAreClustered(): void
    {
        // These points should have the same Morton code at zoom level 1
        $point1 = new Point(45.0, 90.0);
        $point2 = new Point(45.1, 90.1);
        $algorithm = new MortonClusteringAlgorithm();

        $clusters = $algorithm->cluster([$point1, $point2], 1.0);

        $this->assertCount(1, $clusters);
        $this->assertCount(2, $clusters[0]->getPoints());
    }

    public function testPointsWithDifferentMortonCodeAreNotClustered(): void
    {
        // These points will have different Morton codes at zoom level 5
        $point1 = new Point(45.0, 90.0);
        $point2 = new Point(-45.0, -90.0);
        $algorithm = new MortonClusteringAlgorithm();

        $clusters = $algorithm->cluster([$point1, $point2], 5.0);

        $this->assertCount(2, $clusters);
    }

    public function testEmptyPointsArray(): void
    {
        $algorithm = new MortonClusteringAlgorithm();

        $clusters = $algorithm->cluster([], 2.0);

        $this->assertCount(0, $clusters);
    }

    public function testZeroZoomLevel(): void
    {
        $point1 = new Point(10, 20);
        $point2 = new Point(30, 40);
        $algorithm = new MortonClusteringAlgorithm();

        $clusters = $algorithm->cluster([$point1, $point2], 0.0);

        // With zoom 0, everything should be in one big cluster
        $this->assertCount(1, $clusters);
        $this->assertCount(2, $clusters[0]->getPoints());
    }
}
