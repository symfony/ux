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

use Symfony\UX\Map\Exception\InvalidArgumentException;
use Symfony\UX\Map\Icon\Icon;

/**
 * Represents a marker on a map.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class Marker implements Element
{
    /**
     * @param array<string, mixed> $extra Extra data forwarded to the JavaScript side. It can be used in your custom
     *                                    Stimulus controller to benefit from greater flexibility and customization.
     *                                    These data must be serializable to JSON. These data are not used by UX Map.
     */
    public function __construct(
        public readonly Point $position,
        public readonly ?string $title = null,
        public readonly ?InfoWindow $infoWindow = null,
        public readonly array $extra = [],
        public readonly ?string $id = null,
        public readonly ?Icon $icon = null,
    ) {
    }

    /**
     * @return array{
     *     position: array{lat: float, lng: float},
     *     title: string|null,
     *     infoWindow: array<string, mixed>|null,
     *     icon: array{type: value-of<IconType>, width: positive-int, height: positive-int, ...}|null,
     *     extra: array,
     *     id: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'position' => $this->position->toArray(),
            'title' => $this->title,
            'infoWindow' => $this->infoWindow?->toArray(),
            'icon' => $this->icon?->toArray(),
            'extra' => $this->extra,
            'id' => $this->id,
        ];
    }

    /**
     * @param array{
     *     position: array{lat: float, lng: float},
     *     title: string|null,
     *     infoWindow: array<string, mixed>|null,
     *     icon: array{type: value-of<IconType>, width: positive-int, height: positive-int, ...}|null,
     *     extra: array,
     *     id: string|null
     * } $marker
     *
     * @internal
     */
    public static function fromArray(array $marker): self
    {
        if (!isset($marker['position'])) {
            throw new InvalidArgumentException('The "position" parameter is required.');
        }
        $marker['position'] = Point::fromArray($marker['position']);

        if (isset($marker['infoWindow'])) {
            $marker['infoWindow'] = InfoWindow::fromArray($marker['infoWindow']);
        }
        if (isset($marker['icon'])) {
            $marker['icon'] = Icon::fromArray($marker['icon']);
        }

        return new self(...$marker);
    }
}
