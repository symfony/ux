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
use Symfony\UX\Map\Point;

class ClusterTest extends TestCase
{
    public function testAddPointAndGetCenter(): void
    {
        $point1 = new Point(10.0, 20.0);
        $cluster = new Cluster($point1);

        $this->assertEquals(10.0, $cluster->getCenter()->getLatitude());
        $this->assertEquals(20.0, $cluster->getCenter()->getLongitude());

        $point2 = new Point(12.0, 22.0);
        $cluster->addPoint($point2);

        $this->assertEquals(11.0, $cluster->getCenter()->getLatitude());
        $this->assertEquals(21.0, $cluster->getCenter()->getLongitude());
    }

    public function testGetPoints(): void
    {
        $point1 = new Point(10.0, 20.0);
        $point2 = new Point(12.0, 22.0);
        $cluster = new Cluster($point1);
        $cluster->addPoint($point2);

        $points = $cluster->getPoints();
        $this->assertCount(2, $points);
        $this->assertSame($point1, $points[0]);
        $this->assertSame($point2, $points[1]);
    }

    public function testCount(): void
    {
        $cluster = new Cluster(new Point(10.0, 20.0));
        $this->assertCount(1, $cluster);

        $cluster->addPoint(new Point(10.0, 20.0));
        $this->assertCount(2, $cluster);
    }

    public function testIterator(): void
    {
        $point1 = new Point(10.0, 20.0);
        $point2 = new Point(12.0, 22.0);
        $cluster = new Cluster($point1);
        $cluster->addPoint($point2);

        $points = iterator_to_array($cluster);
        $this->assertCount(2, $points);
        $this->assertSame($point1, $points[0]);
        $this->assertSame($point2, $points[1]);
    }

    public function testCreateCluster(): void
    {
        $point1 = new Point(10.0, 20.0);
        $cluster = new Cluster($point1);

        $this->assertCount(1, $cluster->getPoints());
        $this->assertEquals(10.0, $cluster->getCenter()->getLatitude());
        $this->assertEquals(20.0, $cluster->getCenter()->getLongitude());
    }
}
