<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Bridge\Leaflet;

use Symfony\UX\Map\Bridge\Leaflet\Option\AttributionControlOptions;
use Symfony\UX\Map\Bridge\Leaflet\Option\TileLayer;
use Symfony\UX\Map\Bridge\Leaflet\Option\ZoomControlOptions;
use Symfony\UX\Map\MapOptionsInterface;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class LeafletOptions implements MapOptionsInterface
{
    public function __construct(
        private TileLayer|false $tileLayer = new TileLayer(
            url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        ),
        private bool $attributionControl = true,
        private AttributionControlOptions $attributionControlOptions = new AttributionControlOptions(),
        private bool $zoomControl = true,
        private ZoomControlOptions $zoomControlOptions = new ZoomControlOptions(),
    ) {
    }

    public function tileLayer(TileLayer|false $tileLayer): self
    {
        $this->tileLayer = $tileLayer;

        return $this;
    }

    public function attributionControl(bool $enable = true): self
    {
        $this->attributionControl = $enable;

        return $this;
    }

    public function attributionControlOptions(AttributionControlOptions $attributionControlOptions): self
    {
        $this->attributionControl = true;
        $this->attributionControlOptions = $attributionControlOptions;

        return $this;
    }

    public function zoomControl(bool $enable = true): self
    {
        $this->zoomControl = $enable;

        return $this;
    }

    public function zoomControlOptions(ZoomControlOptions $zoomControlOptions): self
    {
        $this->zoomControl = true;
        $this->zoomControlOptions = $zoomControlOptions;

        return $this;
    }

    /**
     * @internal
     */
    public static function fromArray(array $array): MapOptionsInterface
    {
        $array += ['attributionControl' => false, 'zoomControl' => false, 'tileLayer' => false];

        if ($array['tileLayer']) {
            $array['tileLayer'] = TileLayer::fromArray($array['tileLayer']);
        }

        if (isset($array['attributionControlOptions'])) {
            $array['attributionControl'] = true;
            $array['attributionControlOptions'] = AttributionControlOptions::fromArray($array['attributionControlOptions']);
        }

        if (isset($array['zoomControlOptions'])) {
            $array['zoomControl'] = true;
            $array['zoomControlOptions'] = ZoomControlOptions::fromArray($array['zoomControlOptions']);
        }

        return new self(...$array);
    }

    /**
     * @internal
     */
    public function toArray(): array
    {
        $array = [
            'tileLayer' => $this->tileLayer ? $this->tileLayer->toArray() : false,
        ];

        if ($this->attributionControl) {
            $array['attributionControlOptions'] = $this->attributionControlOptions->toArray();
        }

        if ($this->zoomControl) {
            $array['zoomControlOptions'] = $this->zoomControlOptions->toArray();
        }

        return $array;
    }
}
