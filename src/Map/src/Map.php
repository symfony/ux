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
        ];
    }
}
