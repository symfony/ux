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
    public function __construct(
        private readonly ?string $rendererName = null,
        private ?MapOptionsInterface $options = null,
        private ?Point $center = null,
        private ?float $zoom = null,
        private bool $fitBoundsToMarkers = false,
        /**
         * @var array<Marker>
         */
        private array $markers = [],

        /**
         * @var array<Polygon>
         */
        private array $polygons = [],
    ) {
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
        $this->markers[] = $marker;

        return $this;
    }

    public function addPolygon(Polygon $polygon): self
    {
        $this->polygons[] = $polygon;

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
            'options' => (object) ($this->options?->toArray() ?? []),
            'markers' => array_map(static fn (Marker $marker) => $marker->toArray(), $this->markers),
            'polygons' => array_map(static fn (Polygon $polygon) => $polygon->toArray(), $this->polygons),
        ];
    }

    /**
     * @param array{
     *     center?: array{lat: float, lng: float},
     *     zoom?: float,
     *     markers?: list<array>,
     *     polygons?: list<array>,
     *     fitBoundsToMarkers?: bool,
     *     options?: object,
     * } $map
     *
     * @internal
     */
    public static function fromArray(array $map): self
    {
        $map['fitBoundsToMarkers'] = true;

        if (isset($map['center'])) {
            $map['center'] = Point::fromArray($map['center']);
        }

        if (isset($map['zoom']) || isset($map['center'])) {
            $map['fitBoundsToMarkers'] = false;
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

        return new self(...$map);
    }
}
