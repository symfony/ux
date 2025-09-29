<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map;

use Symfony\UX\Map\Exception\InvalidArgumentException;

/**
 * Represents a polygon on a map.
 *
 * @author [Pierre Svgnt]
 */
final class Polygon implements Element
{
    /**
     * @param array<Point>|array<array<Point>> $points a list of point representing the polygon, or a list of paths (each path is an array of points) representing a polygon with holes
     * @param array<string, mixed>             $extra  Extra data forwarded to the JavaScript side. It can be used in your custom
     *                                                 Stimulus controller to benefit from greater flexibility and customization.
     *                                                 These data must be serializable to JSON. These data are not used by UX Map.
     */
    public function __construct(
        private readonly array $points,
        private readonly ?string $title = null,
        private readonly ?InfoWindow $infoWindow = null,
        private readonly array $extra = [],
        public readonly ?string $id = null,
    ) {
        if (null !== $title) {
            trigger_deprecation('symfony/ux-map', '2.30', 'The "title" parameter is deprecated and will be removed in 3.0. Use "infoWindow" instead.');
        }
    }

    /**
     * Convert the polygon to an array representation.
     *
     * @return array{
     *     points: array<array{lat: float, lng: float}>|array<array{lat: float, lng: float}>,
     *     title: string|null,
     *     infoWindow: array<string, mixed>|null,
     *     extra: array,
     *     id: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'points' => current($this->points) instanceof Point
                ? array_map(fn (Point $point) => $point->toArray(), $this->points)
                : array_map(fn (array $path) => array_map(fn (Point $point) => $point->toArray(), $path), $this->points),
            'title' => $this->title,
            'infoWindow' => $this->infoWindow?->toArray(),
            'extra' => $this->extra,
            'id' => $this->id,
        ];
    }

    /**
     * @param array{
     *     points: array<array{lat: float, lng: float}|array<array{lat: float, lng: float}>>,
     *     title: string|null,
     *     infoWindow: array<string, mixed>|null,
     *     extra: array,
     *     id: string|null
     * } $polygon
     *
     * @internal
     */
    public static function fromArray(array $polygon): self
    {
        if (!isset($polygon['points'])) {
            throw new InvalidArgumentException('The "points" parameter is required.');
        }

        $polygon['points'] = isset($polygon['points'][0]['lat'], $polygon['points'][0]['lng'])
            ? array_map(Point::fromArray(...), $polygon['points'])
            : array_map(fn (array $points) => array_map(Point::fromArray(...), $points), $polygon['points']);

        if (isset($polygon['infoWindow'])) {
            $polygon['infoWindow'] = InfoWindow::fromArray($polygon['infoWindow']);
        }

        return new self(...$polygon);
    }
}
