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

/**
 * Represents a marker on a map.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final readonly class Marker
{
    public function __construct(
        private Point $position,
        private ?string $title = null,
        private ?InfoWindow $infoWindow = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'position' => $this->position->toArray(),
            'title' => $this->title,
            'infoWindow' => $this->infoWindow?->toArray(),
        ];
    }
}
