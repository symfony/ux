<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Tests\Distance;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Map\Distance\DistanceCalculator;
use Symfony\UX\Map\Distance\DistanceCalculatorInterface;
use Symfony\UX\Map\Distance\HaversineDistanceCalculator;
use Symfony\UX\Map\Distance\SphericalCosineDistanceCalculator;
use Symfony\UX\Map\Distance\VincentyDistanceCalculator;
use Symfony\UX\Map\Point;

class DistanceCalculatorTest extends TestCase
{
    public function testCalculateDistanceUseCalculator(): void
    {
        $calculator = new class implements DistanceCalculatorInterface {
            public function calculateDistance(Point $point1, Point $point2): float
            {
                return $point1->getLatitude() + $point2->getLongitude();
            }
        };

        $distanceCalculator = new DistanceCalculator($calculator);
        $this->assertSame(0.0, $distanceCalculator->calculateDistance(new Point(0.0, 0.0), new Point(0.0, 0.0)));
        $this->assertSame(90.0, $distanceCalculator->calculateDistance(new Point(45.0, 0.0), new Point(0.0, 45.0)));
    }

    /**
     * Test that the non-reference calculators (Haversine and Spherical Cosine)
     * produce results close to the reference (Vincenty) within an acceptable margin.
     *
     * @dataProvider distanceAccuracyProvider
     */
    public function testAccuracyAgainstVincenty(Point $point1, Point $point2, float $tolerance): void
    {
        $vincenty = new VincentyDistanceCalculator();
        $referenceDistance = $vincenty->calculateDistance($point1, $point2);

        $calculators = [
            new HaversineDistanceCalculator(),
            new SphericalCosineDistanceCalculator(),
        ];

        foreach ($calculators as $calculator) {
            $distance = $calculator->calculateDistance($point1, $point2);
            $difference = abs($referenceDistance - $distance);

            $this->assertLessThanOrEqual($tolerance, $difference, \sprintf('%s difference (%.2f m) exceeds tolerance (%.2f m).', $calculator::class, $difference, $tolerance));
        }
    }

    /**
     * @return array<array{Point, Point, float}>
     */
    public static function distanceAccuracyProvider(): array
    {
        return [
            'Short distance: around the equator (111.32m)' => [
                new Point(0.0, 0.0),
                new Point(0.0, 0.001),
                .25,
            ],
            'Small distance: Highbury to Emirates (445.61m)' => [
                new Point(51.55703, -0.10280),
                new Point(51.55509, -0.10844),
                2.5,
            ],
            'Moderate distance: Rennes to Saint-Malo (64.537 km)' => [
                new Point(48.1173, -1.6778),
                new Point(48.6497, -2.0258),
                50.0,
            ],
            'Moderate distance: Paris to London  (343.556 km)' => [
                new Point(48.8566, 2.3522),
                new Point(51.5074, -0.1278),
                500.0,
            ],
            'Long distance: Metropolis to Gotham City (1,144.291 km)' => [
                new Point(40.7128, -74.0060),
                new Point(41.8781, -87.6298),
                5000.0,
            ],
            'Long distance: New York to Los Angeles (3,935.746 km)' => [
                new Point(40.7128, -74.0060),
                new Point(34.0522, -118.2437),
                10000.0,
            ],
        ];
    }
}
