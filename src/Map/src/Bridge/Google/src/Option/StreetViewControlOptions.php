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
 * Options for the rendering of the Street View pegman control on the map.
 *
 * @see https://developers.google.com/maps/documentation/javascript/reference/control#StreetViewControlOptions
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final readonly class StreetViewControlOptions
{
    public function __construct(
        private ControlPosition $position = ControlPosition::INLINE_END_BLOCK_END,
    ) {
    }

    public function toArray(): array
    {
        return [
            'position' => $this->position->value,
        ];
    }
}
