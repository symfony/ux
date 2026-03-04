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
 * Represents a map.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class Map
{
    private Markers $markers;
    private Polygons $polygons;
    private Polylines $polylines;
    private Circles $circles;
    private Rectangles $rectangles;

    /**
     * @param Marker[]             $markers
     * @param Polygon[]            $polygons
     * @param Polyline[]           $polylines
     * @param Circle[]             $circles
     * @param Rectangles[]         $rectangles
     * @param array<string, mixed> $extra      Extra data forwarded to the JavaScript side. It can be used in your custom
     *                                         Stimulus controller to benefit from greater flexibility and customization.
     *                                         These data must be serializable to JSON. These data are not used by UX Map.
     */
    public function __construct(
        private readonly ?string $rendererName = null,
        private ?MapOptionsInterface $options = null,
        private ?Point $center = null,
        private ?float $zoom = null,
        private bool $fitBoundsToMarkers = false,
        array $markers = [],
        array $polygons = [],
        array $polylines = [],
        array $circles = [],
        array $rectangles = [],
        private array $extra = [],
        private ?float $minZoom = null,
        private ?float $maxZoom = null,
    ) {
        $this->markers = new Markers($markers);
        $this->polygons = new Polygons($polygons);
        $this->polylines = new Polylines($polylines);
        $this->circles = new Circles($circles);
        $this->rectangles = new Rectangles($rectangles);
        $this->validateZooms();
    }

    public function getRendererName(): ?string
    {
        return $this->rendererName;
    }

    public function center(Point $center): self
    {
        $this->center = $center;

        return $this;
    }

    public function zoom(float $zoom): self
    {
        $this->zoom = $zoom;
        $this->validateZooms();

        return $this;
    }

    public function minZoom(float $minZoom): self
    {
        $this->minZoom = $minZoom;
        $this->validateZooms();

        return $this;
    }

    public function maxZoom(float $maxZoom): self
    {
        $this->maxZoom = $maxZoom;
        $this->validateZooms();

        return $this;
    }

    public function fitBoundsToMarkers(bool $enable = true): self
    {
        $this->fitBoundsToMarkers = $enable;

        return $this;
    }

    public function options(MapOptionsInterface $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function getOptions(): ?MapOptionsInterface
    {
        return $this->options;
    }

    public function hasOptions(): bool
    {
        return null !== $this->options;
    }

    public function addMarker(Marker $marker): self
    {
        $this->markers->add($marker);

        return $this;
    }

    public function removeMarker(Marker|string $markerOrId): self
    {
        $this->markers->remove($markerOrId);

        return $this;
    }

    public function removeAllMarkers(): self
    {
        $this->markers->removeAll();

        return $this;
    }

    public function addPolygon(Polygon $polygon): self
    {
        $this->polygons->add($polygon);

        return $this;
    }

    public function removePolygon(Polygon|string $polygonOrId): self
    {
        $this->polygons->remove($polygonOrId);

        return $this;
    }

    public function removeAllPolygons(): self
    {
        $this->polygons->removeAll();

        return $this;
    }

    public function addPolyline(Polyline $polyline): self
    {
        $this->polylines->add($polyline);

        return $this;
    }

    public function removePolyline(Polyline|string $polylineOrId): self
    {
        $this->polylines->remove($polylineOrId);

        return $this;
    }

    public function removeAllPolylines(): self
    {
        $this->polylines->removeAll();

        return $this;
    }

    public function addCircle(Circle $circle): self
    {
        $this->circles->add($circle);

        return $this;
    }

    public function removeCircle(Circle|string $circleOrId): self
    {
        $this->circles->remove($circleOrId);

        return $this;
    }

    public function removeAllCircles(): self
    {
        $this->circles->removeAll();

        return $this;
    }

    public function addRectangle(Rectangle $rectangle): self
    {
        $this->rectangles->add($rectangle);

        return $this;
    }

    public function removeRectangle(Rectangle|string $rectangleOrId): self
    {
        $this->rectangles->remove($rectangleOrId);

        return $this;
    }

    public function removeAllRectangles(): self
    {
        $this->rectangles->removeAll();

        return $this;
    }

    /**
     * @param array<string, mixed> $extra Extra data forwarded to the JavaScript side. It can be used in your custom
     *                                    Stimulus controller to benefit from greater flexibility and customization.
     *                                    These data must be serializable to JSON. These data are not used by UX Map.
     */
    public function extra(array $extra): self
    {
        $this->extra = $extra;

        return $this;
    }

    public function toArray(): array
    {
        if (!$this->fitBoundsToMarkers) {
            if (null === $this->center) {
                throw new InvalidArgumentException('The map "center" must be explicitly set when not enabling "fitBoundsToMarkers" feature.');
            }

            if (null === $this->zoom) {
                throw new InvalidArgumentException('The map "zoom" must be explicitly set when not enabling "fitBoundsToMarkers" feature.');
            }
        }

        return [
            'center' => $this->center?->toArray(),
            'zoom' => $this->zoom,
            'fitBoundsToMarkers' => $this->fitBoundsToMarkers,
            'options' => $this->options ? MapOptionsNormalizer::normalize($this->options) : [],
            'markers' => $this->markers->toArray(),
            'polygons' => $this->polygons->toArray(),
            'polylines' => $this->polylines->toArray(),
            'circles' => $this->circles->toArray(),
            'rectangles' => $this->rectangles->toArray(),
            // Send `null` if empty instead of `[]`, because Stimulus Controller values validation expect an Object,
            // and sending `(object) $this->extra` mess with LiveComponent hydration checksum validation
            'extra' => [] === $this->extra ? null : $this->extra,
            'minZoom' => $this->minZoom,
            'maxZoom' => $this->maxZoom,
        ];
    }

    /**
     * @param array{
     *     center?: array{lat: float, lng: float},
     *     zoom?: float,
     *     markers?: list<array>,
     *     polygons?: list<array>,
     *     polylines?: list<array>,
     *     circles?: list<array>,
     *     rectangles?: list<array>,
     *     fitBoundsToMarkers?: bool,
     *     options?: array<string, mixed>,
     *     extra?: array<string, mixed>,
     *     minZoom?: float,
     *     maxZoom?: float,
     * } $map
     *
     * @internal
     */
    public static function fromArray(array $map): self
    {
        if (isset($map['options'])) {
            $map['options'] = [] === $map['options'] ? null : MapOptionsNormalizer::denormalize($map['options']);
        }

        if (isset($map['center'])) {
            $map['center'] = Point::fromArray($map['center']);
        }

        $map['markers'] ??= [];
        if (!\is_array($map['markers'])) {
            throw new InvalidArgumentException('The "markers" parameter must be an array.');
        }
        $map['markers'] = array_map(Marker::fromArray(...), $map['markers']);

        $map['polygons'] ??= [];
        if (!\is_array($map['polygons'])) {
            throw new InvalidArgumentException('The "polygons" parameter must be an array.');
        }
        $map['polygons'] = array_map(Polygon::fromArray(...), $map['polygons']);

        $map['polylines'] ??= [];
        if (!\is_array($map['polylines'])) {
            throw new InvalidArgumentException('The "polylines" parameter must be an array.');
        }
        $map['polylines'] = array_map(Polyline::fromArray(...), $map['polylines']);

        $map['circles'] ??= [];
        if (!\is_array($map['circles'])) {
            throw new InvalidArgumentException('The "circles" parameter must be an array.');
        }
        $map['circles'] = array_map(Circle::fromArray(...), $map['circles']);

        $map['rectangles'] ??= [];
        if (!\is_array($map['rectangles'])) {
            throw new InvalidArgumentException('The "rectangles" parameter must be an array.');
        }
        $map['rectangles'] = array_map(Rectangle::fromArray(...), $map['rectangles']);

        $map['extra'] ??= [];

        return new self(...$map);
    }

    private function validateZooms(): void
    {
        if (null !== $this->zoom && $this->zoom < 0) {
            throw new InvalidArgumentException('The "zoom" must be greater than or equal to 0.');
        }

        if (null !== $this->minZoom && $this->minZoom < 0) {
            throw new InvalidArgumentException('The "minZoom" must be greater than or equal to 0.');
        }

        if (null !== $this->maxZoom && $this->maxZoom < 0) {
            throw new InvalidArgumentException('The "maxZoom" must be greater than or equal to 0.');
        }

        if (null !== $this->minZoom && null !== $this->maxZoom && $this->minZoom > $this->maxZoom) {
            throw new InvalidArgumentException('The "minZoom" must be less than or equal to "maxZoom".');
        }

        if (null !== $this->zoom && null !== $this->minZoom && $this->zoom < $this->minZoom) {
            throw new InvalidArgumentException('The "zoom" must be greater than or equal to "minZoom".');
        }

        if (null !== $this->zoom && null !== $this->maxZoom && $this->zoom > $this->maxZoom) {
            throw new InvalidArgumentException('The "zoom" must be less than or equal to "maxZoom".');
        }
    }
}
