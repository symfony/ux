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
 * Cluster representation.
 *
 * @implements \IteratorAggregate<int, Point>
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class Cluster implements \Countable, \IteratorAggregate
{
    /**
     * @var Point[]
     */
    private array $points = [];

    private float $sumLat = 0.0;
    private float $sumLng = 0.0;
    private int $count = 0;

    /**
     * Initializes the cluster with an initial point.
     */
    public function __construct(Point $initialPoint)
    {
        $this->addPoint($initialPoint);
    }

    public function addPoint(Point $point): void
    {
        $this->points[] = $point;
        $this->sumLat += $point->getLatitude();
        $this->sumLng += $point->getLongitude();
        ++$this->count;
    }

    /**
     * Returns the center of the cluster as a Point.
     */
    public function getCenter(): Point
    {
        return new Point($this->sumLat / $this->count, $this->sumLng / $this->count);
    }

    /**
     * @return non-empty-list<Point>
     */
    public function getPoints(): array
    {
        return $this->points;
    }

    /**
     * Returns the number of points in the cluster.
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * @return \Traversable<int, Point>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->points);
    }
}
