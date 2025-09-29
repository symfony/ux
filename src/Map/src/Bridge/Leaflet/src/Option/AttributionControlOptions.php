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
 * Options for the rendering of the attribution control.
 *
 * @see https://leafletjs.com/reference.html#control-zoom
 */
final class AttributionControlOptions
{
    public function __construct(
        private readonly ControlPosition $position = ControlPosition::BOTTOM_RIGHT,
        private readonly string|false $prefix = 'Leaflet',
    ) {
    }

    /**
     * @internal
     */
    public static function fromArray(array $array): self
    {
        return new self(
            position: ControlPosition::from($array['position']),
            prefix: $array['prefix'],
        );
    }

    /**
     * @internal
     */
    public function toArray(): array
    {
        return [
            'position' => $this->position->value,
            'prefix' => $this->prefix,
        ];
    }
}
