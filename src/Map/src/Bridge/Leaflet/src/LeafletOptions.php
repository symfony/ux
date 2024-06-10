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
        private TileLayer $tileLayer = new TileLayer(
            url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        ),
    ) {
    }

    public function tileLayer(TileLayer $tileLayer): self
    {
        $this->tileLayer = $tileLayer;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'tileLayer' => $this->tileLayer->toArray(),
        ];
    }
}
