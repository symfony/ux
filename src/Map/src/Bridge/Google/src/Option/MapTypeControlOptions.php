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
final readonly class MapTypeControlOptions
{
    /**
     * @param array<'hybrid'|'roadmap'|'satellite'|'terrain'|string> $mapTypeIds
     */
    public function __construct(
        private array $mapTypeIds = [],
        private ControlPosition $position = ControlPosition::BLOCK_START_INLINE_START,
        private MapTypeControlStyle $style = MapTypeControlStyle::DEFAULT,
    ) {
    }

    public function toArray(): array
    {
        return [
            'mapTypeIds' => $this->mapTypeIds,
            'position' => $this->position->value,
            'style' => $this->style->value,
        ];
    }
}
