<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Data;

use Symfony\UX\Map\Circle;
use Symfony\UX\Map\InfoWindow;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\MapOptionsInterface;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;
use Symfony\UX\Map\Polygon;
use Symfony\UX\Map\Polyline;

final class MapBuilder
{
    public const MARKERS = 2_000;
    public const POLYGONS = 100;
    public const POLYLINES = 50;
    public const CIRCLES = 20;
    private const CENTER_LATITUDE = 48.8566;
    private const CENTER_LONGITUDE = 2.3522;
    private const STEP = 0.005;
    private const COLUMNS = 50;
    private const ROWS = 40;

    public function build(string $rendererName, MapOptionsInterface $options): Map
    {
        $map = new Map(
            rendererName: $rendererName,
            options: $options,
            center: new Point(self::CENTER_LATITUDE, self::CENTER_LONGITUDE),
            zoom: 12,
        );

        foreach ($this->markers() as $marker) {
            $map->addMarker($marker);
        }
        foreach ($this->polygons() as $polygon) {
            $map->addPolygon($polygon);
        }
        foreach ($this->polylines() as $polyline) {
            $map->addPolyline($polyline);
        }
        foreach ($this->circles() as $circle) {
            $map->addCircle($circle);
        }

        return $map;
    }

    /**
     * @return iterable<Marker>
     */
    private function markers(): iterable
    {
        for ($i = 1; $i <= self::MARKERS; ++$i) {
            $row = intdiv($i - 1, self::COLUMNS);
            $col = ($i - 1) % self::COLUMNS;

            yield new Marker(
                position: $this->point($row, $col),
                title: \sprintf('Marker %d', $i),
                infoWindow: new InfoWindow(
                    headerContent: \sprintf('<b>Marker %d</b>', $i),
                    content: \sprintf('Row %d, column %d', $row, $col),
                ),
                id: \sprintf('marker-%d', $i),
            );
        }
    }

    /**
     * @return iterable<Polygon>
     */
    private function polygons(): iterable
    {
        for ($i = 1; $i <= self::POLYGONS; ++$i) {
            $row = intdiv($i - 1, self::COLUMNS) * 4 + 1;
            $col = ($i - 1) % self::COLUMNS;

            yield new Polygon(
                points: [
                    $this->point($row, $col, 0.2, 0.2),
                    $this->point($row, $col, 0.2, 0.8),
                    $this->point($row, $col, 0.8, 0.5),
                ],
                id: \sprintf('polygon-%d', $i),
            );
        }
    }

    /**
     * @return iterable<Polyline>
     */
    private function polylines(): iterable
    {
        for ($i = 1; $i <= self::POLYLINES; ++$i) {
            $row = intdiv($i - 1, self::COLUMNS) * 4 + 3;
            $col = ($i - 1) % self::COLUMNS;

            yield new Polyline(
                points: [
                    $this->point($row, $col),
                    $this->point($row, $col, 0.5, 0.5),
                    $this->point($row, $col, 0, 1),
                ],
                id: \sprintf('polyline-%d', $i),
            );
        }
    }

    /**
     * @return iterable<Circle>
     */
    private function circles(): iterable
    {
        for ($i = 1; $i <= self::CIRCLES; ++$i) {
            $row = self::ROWS - 1 - intdiv($i - 1, self::COLUMNS);
            $col = ($i - 1) * 2 % self::COLUMNS;

            yield new Circle(
                center: $this->point($row, $col),
                radius: 200 + 10 * $i,
                id: \sprintf('circle-%d', $i),
            );
        }
    }

    private function point(int $row, int $col, float $rowOffset = 0.0, float $colOffset = 0.0): Point
    {
        return new Point(
            self::CENTER_LATITUDE + ($row + $rowOffset - 20) * self::STEP,
            self::CENTER_LONGITUDE + ($col + $colOffset - 25) * self::STEP,
        );
    }
}
