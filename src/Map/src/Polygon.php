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
 * Represents a polygon on a map.
 *
 * @author [Pierre Svgnt]
 */
final readonly class Polygon
{
    /**
     * @param array<string, mixed> $extra Extra data, can be used by the developer to store additional information and use them later JavaScript side
     */
    public function __construct(
        private array $points,
        private ?string $title = null,
        private ?InfoWindow $infoWindow = null,
        private array $extra = [],
    ) {
    }

    /**
     * Convert the polygon to an array representation.
     */
    public function toArray(): array
    {
        return [
            'points' => array_map(fn (Point $point) => $point->toArray(), $this->points),
            'title' => $this->title,
            'infoWindow' => $this->infoWindow?->toArray(),
            'extra' => (object) $this->extra,
        ];
    }

    /**
     * @param array{
     *     points: array<array{lat: float, lng: float}>,
     *     title: string|null,
     *     infoWindow: array<string, mixed>|null,
     *     extra: object,
     * } $polygon
     *
     * @internal
     */
    public static function fromArray(array $polygon): self
    {
        if (!isset($polygon['points'])) {
            throw new InvalidArgumentException('The "points" parameter is required.');
        }
        $polygon['points'] = array_map(Point::fromArray(...), $polygon['points']);

        if (isset($polygon['infoWindow'])) {
            $polygon['infoWindow'] = InfoWindow::fromArray($polygon['infoWindow']);
        }

        return new self(...$polygon);
    }
}
