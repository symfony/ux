<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Twig;

use Symfony\UX\Map\Circle;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;
use Symfony\UX\Map\Polygon;
use Symfony\UX\Map\Polyline;
use Symfony\UX\Map\Rectangle;
use Symfony\UX\Map\Renderer\RendererInterface;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class MapRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly RendererInterface $renderer,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $markers
     * @param array<string, mixed> $polygons
     * @param array<string, mixed> $polylines
     * @param array<string, mixed> $circles
     * @param array<string, mixed> $rectangles
     */
    public function renderMap(
        ?Map $map = null,
        array $attributes = [],
        ?array $center = null,
        ?float $zoom = null,
        ?array $markers = null,
        ?array $polygons = null,
        ?array $polylines = null,
        ?array $circles = null,
        ?array $rectangles = null,
        ?float $minZoom = null,
        ?float $maxZoom = null,
        ?bool $fitBoundsToMarkers = null,
    ): string {
        if ($map instanceof Map) {
            if (null !== $center || null !== $zoom || $markers || $polygons || $polylines || $circles || $rectangles || $minZoom || $maxZoom || null !== $fitBoundsToMarkers) {
                throw new \InvalidArgumentException('It is not allowed to pass both a Map object and other parameters (like "center", "zoom", "markers", etc...) to the "renderMap" method. Please use either a Map object or the individual parameters.');
            }

            return $this->renderer->renderMap($map, $attributes);
        }

        $map = new Map();
        foreach ($markers ?? [] as $marker) {
            $map->addMarker(Marker::fromArray($marker));
        }
        foreach ($polygons ?? [] as $polygon) {
            $map->addPolygon(Polygon::fromArray($polygon));
        }
        foreach ($polylines ?? [] as $polyline) {
            $map->addPolyline(Polyline::fromArray($polyline));
        }
        foreach ($circles ?? [] as $circle) {
            $map->addCircle(Circle::fromArray($circle));
        }
        foreach ($rectangles ?? [] as $rectangle) {
            $map->addRectangle(Rectangle::fromArray($rectangle));
        }
        if (null !== $center) {
            $map->center(Point::fromArray($center));
        }
        if (null !== $zoom) {
            $map->zoom($zoom);
        }
        if (null !== $minZoom) {
            $map->minZoom($minZoom);
        }
        if (null !== $maxZoom) {
            $map->maxZoom($maxZoom);
        }
        if (null !== $fitBoundsToMarkers) {
            $map->fitBoundsToMarkers($fitBoundsToMarkers);
        }

        return $this->renderer->renderMap($map, $attributes);
    }

    public function render(array $args = []): string
    {
        $map = array_intersect_key($args, array_flip(['map', 'center', 'zoom', 'markers', 'polygons', 'polylines', 'circles', 'rectangles', 'minZoom', 'maxZoom', 'fitBoundsToMarkers']));
        $attributes = array_diff_key($args, $map);

        return $this->renderMap(...$map, attributes: $attributes);
    }
}
