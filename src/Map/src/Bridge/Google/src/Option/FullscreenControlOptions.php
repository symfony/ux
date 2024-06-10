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
 * Options for the rendering of the fullscreen control.
 *
 * @see https://developers.google.com/maps/documentation/javascript/reference/control#FullscreenControlOptions
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final readonly class FullscreenControlOptions
{
    public function __construct(
        private ControlPosition $position = ControlPosition::INLINE_END_BLOCK_START,
    ) {
    }

    public function toArray(): array
    {
        return [
            'position' => $this->position->value,
        ];
    }
}
