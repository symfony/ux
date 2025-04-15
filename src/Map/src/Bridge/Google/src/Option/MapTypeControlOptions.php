<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Bridge\Google\Option;

/**
 * Options for the rendering of the map type control.
 *
 * @see https://developers.google.com/maps/documentation/javascript/reference/control#MapTypeControlOptions
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class MapTypeControlOptions
{
    /**
     * @param array<'hybrid'|'roadmap'|'satellite'|'terrain'|string> $mapTypeIds
     */
    public function __construct(
        private readonly array $mapTypeIds = [],
        private readonly ControlPosition $position = ControlPosition::BLOCK_START_INLINE_START,
        private readonly MapTypeControlStyle $style = MapTypeControlStyle::DEFAULT,
    ) {
    }

    /**
     * @internal
     */
    public static function fromArray(array $array): self
    {
        return new self(
            mapTypeIds: $array['mapTypeIds'],
            position: ControlPosition::from($array['position']),
            style: MapTypeControlStyle::from($array['style']),
        );
    }

    /**
     * @internal
     */
    public function toArray(): array
    {
        return [
            'mapTypeIds' => $this->mapTypeIds,
            'position' => $this->position->value,
            'style' => $this->style->value,
        ];
    }
}
