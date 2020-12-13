<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Leafletjs\Model;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 * @author Michael Cramer <michael@bigmichi1.de>
 *
 * @final
 * @experimental
 */
class Map
{
    private $mapOptions = [];
    private $attributes = [];
    private $lon;
    private $lat;
    private $zoom;

    public function __construct(float $lon, float $lat, int $zoom)
    {
        $this->lon = $lon;
        $this->lat = $lat;
        $this->zoom = $zoom;
    }

    public function setMapOptions(array $mapOptions): void
    {
        $this->mapOptions = $mapOptions;
    }

    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    public function getDataController(): ?string
    {
        return $this->attributes['data-controller'] ?? null;
    }

    public function getMapOptions(): array
    {
        return $this->mapOptions;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getLon(): float
    {
        return $this->lon;
    }

    public function getLat(): float
    {
        return $this->lat;
    }

    public function getZoom(): int
    {
        return $this->zoom;
    }
}
