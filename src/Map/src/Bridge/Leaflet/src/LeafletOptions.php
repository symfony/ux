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

use Symfony\UX\Map\Bridge\Leaflet\Option\TileLayer;
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
        private array $extra = [],
    ) {
    }

    public function tileLayer(TileLayer|false $tileLayer): self
    {
        $this->tileLayer = $tileLayer;

        return $this;
    }

    public function extra(array $extra): self
    {
        $this->extra = $extra;

        return $this;
    }

    /**
     * @internal
     */
    public static function fromArray(array $array): MapOptionsInterface
    {
        return new self(
            tileLayer: $array['tileLayer'] ? TileLayer::fromArray($array['tileLayer']) : false,
            extra: $array['extra'] ?? []
        );
    }

    /**
     * @internal
     */
    public function toArray(): array
    {
        return [
            'tileLayer' => $this->tileLayer ? $this->tileLayer->toArray() : false,
            'extra' => $this->extra,
        ];
    }
}
