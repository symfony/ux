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
use Symfony\UX\Map\Cluster\ClusteringAlgorithmInterface;
use Symfony\UX\Map\Cluster\GridClusteringAlgorithm;
use Symfony\UX\Map\Cluster\MortonClusteringAlgorithm;
use Symfony\UX\Map\Point;

class ClusteringPerformanceTest extends TestCase
{
    /**
     * @var array<float>
     */
    private const ZOOMS = [
        2.0,
        5.0,
        8.0,
    ];

    /**
     * @var array<string>
     */
    private const ALGORITHMS = [
        GridClusteringAlgorithm::class,
        MortonClusteringAlgorithm::class,
    ];

    /**
     * @return iterable<array{0: ClusteringAlgorithmInterface, 1: float}>
     */
    public static function algorithmProvider(): iterable
    {
        foreach (self::ZOOMS as $zoom) {
            foreach (self::ALGORITHMS as $algorithm) {
                yield $algorithm.' '.$zoom => [new $algorithm(), $zoom];
            }
        }
    }

    /**
     * Scenario 1: Large number of points (50,000), concentrated area (Paris region).
     *
     * @dataProvider algorithmProvider
     */
    public function testScenarioRegion50000(ClusteringAlgorithmInterface $algorithm, float $zoom)
    {
        $points = $this->generatePoints(50000, 48.8, 49, 2.2, 2.5);

        $this->runPerformanceTest($algorithm, $points, $zoom);
    }

    /**
     * Scenario 2: Moderate number of points (5,000), broad area (France and surroundings).
     *
     * @dataProvider algorithmProvider
     */
    public function testScenarioCountry5000(ClusteringAlgorithmInterface $algorithm, float $zoom)
    {
        $points = $this->generatePoints(5000, 30, 60, -10, 35);

        $this->runPerformanceTest($algorithm, $points, $zoom);
    }

    /**
     * Scenario 3: Very large number of points (100,000), global distribution.
     *
     * @dataProvider algorithmProvider
     */
    public function testScenarioWorld100000(ClusteringAlgorithmInterface $algorithm, float $zoom)
    {
        $points = $this->generatePoints(100000, -90, 90, -180, 180);

        $this->runPerformanceTest($algorithm, $points, $zoom);
    }

    /**
     * @param array<Point> $points
     */
    private function runPerformanceTest(ClusteringAlgorithmInterface $algorithm, array $points, float $zoom): void
    {
        $startTime = microtime(true);
        $algorithm->cluster($points, $zoom);
        $elapsed = microtime(true) - $startTime;

        $this->assertLessThan(2.0, $elapsed, $algorithm::class." took too long: {$elapsed} seconds (zoom {$zoom}, ".\count($points).' points)');
    }

    private function generatePoints(int $count, float $latMin, float $latMax, float $lngMin, float $lngMax): array
    {
        $points = [];
        for ($i = 0; $i < $count; ++$i) {
            $lat = random_int((int) ($latMin * 100), (int) ($latMax * 100)) / 100.0;
            $lng = random_int((int) ($lngMin * 100), (int) ($lngMax * 100)) / 100.0;
            $points[] = new Point($lat, $lng);
        }

        return $points;
    }
}
