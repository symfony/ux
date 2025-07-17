<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Bridge\Leaflet\Option;

/**
 * Represents a tile layer for a Leaflet map.
 *
 * @see https://leafletjs.com/reference.html#tilelayer
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class TileLayer
{
    /**
     * @param array<mixed> $options
     */
    public function __construct(
        private readonly string $url,
        private readonly string $attribution,
        private readonly array $options = [],
    ) {
    }

    /**
     * @internal
     */
    public static function fromArray(array $array): self
    {
        return new self(
            url: $array['url'],
            attribution: $array['attribution'],
            options: $array['options'],
        );
    }

    /**
     * @internal
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'attribution' => $this->attribution,
            'options' => $this->options,
        ];
    }
}
