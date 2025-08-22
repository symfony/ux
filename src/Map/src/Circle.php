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

/**
 * Represents a circle on a map.
 *
 * @author Valmont Pehaut-Pietri <valmont.pehaut-pietri@proton.me>
 */
final class Circle implements Element
{
    /**
     * @param array<string, mixed> $extra  Extra data forwarded to the JavaScript side. It can be used in your custom
     *                                     Stimulus controller to benefit from greater flexibility and customization.
     *                                     These data must be serializable to JSON. These data are not used by UX Map.
     * @param float                $radius The radius of the circle in meters
     */
    public function __construct(
        public readonly Point $center,
        public readonly float $radius,
        public readonly ?string $title = null,
        public readonly ?InfoWindow $infoWindow = null,
        public readonly array $extra = [],
        public readonly ?string $id = null,
    ) {
        if (null !== $title) {
            trigger_deprecation('symfony/ux-map', '2.30', 'The "title" parameter is deprecated and will be removed in 3.0. Use "infoWindow" instead.');
        }

        if ($radius <= 0) {
            throw new InvalidArgumentException(\sprintf('Radius must be greater than 0, "%s" given.', $radius));
        }
    }

    /**
     * Convert the circle to an array representation.
     *
     * @return array{
     *     center: array{lat: float, lng: float},
     *     radius: float,
     *     title: string|null,
     *     infoWindow: array<string, mixed>|null,
     *     extra: array,
     *     id: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'center' => $this->center->toArray(),
            'radius' => $this->radius,
            'title' => $this->title,
            'infoWindow' => $this->infoWindow?->toArray(),
            'extra' => $this->extra,
            'id' => $this->id,
        ];
    }

    /**
     * @param array{
     *     center: array{lat: float, lng: float},
     *     radius: float,
     *     title: string|null,
     *     infoWindow: array<string, mixed>|null,
     *     extra: array,
     *     id: string|null
     * } $circle
     *
     * @internal
     */
    public static function fromArray(array $circle): self
    {
        if (!isset($circle['center'])) {
            throw new InvalidArgumentException('The "center" parameter is required.');
        }
        if (!isset($circle['radius'])) {
            throw new InvalidArgumentException('The "radius" parameter is required.');
        }

        $circle['center'] = Point::fromArray($circle['center']);

        if (isset($circle['infoWindow'])) {
            $circle['infoWindow'] = InfoWindow::fromArray($circle['infoWindow']);
        }

        return new self(...$circle);
    }
}
