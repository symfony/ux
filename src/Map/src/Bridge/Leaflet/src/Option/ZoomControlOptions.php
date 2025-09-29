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
 * Options for the rendering of the zoom control.
 *
 * @see https://leafletjs.com/reference.html#control-zoom
 */
final class ZoomControlOptions
{
    public function __construct(
        private readonly ControlPosition $position = ControlPosition::TOP_LEFT,
        private readonly string $zoomInText = '<span aria-hidden="true">+</span>',
        private readonly string $zoomInTitle = 'Zoom in',
        private readonly string $zoomOutText = '<span aria-hidden="true">&#x2212;</span>',
        private readonly string $zoomOutTitle = 'Zoom out',
    ) {
    }

    /**
     * @internal
     */
    public static function fromArray(array $array): self
    {
        if (isset($array['position'])) {
            $array['position'] = ControlPosition::from($array['position']);
        }

        return new self(...$array);
    }

    /**
     * @internal
     */
    public function toArray(): array
    {
        return [
            'position' => $this->position->value,
            'zoomInText' => $this->zoomInText,
            'zoomInTitle' => $this->zoomInTitle,
            'zoomOutText' => $this->zoomOutText,
            'zoomOutTitle' => $this->zoomOutTitle,
        ];
    }
}
