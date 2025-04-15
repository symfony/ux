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
 * Represents a polyline on a map.
 *
 * @author [Sylvain Blondeau]
 */
final class Polyline implements Element
{
    /**
     * @param array<string, mixed> $extra Extra data, can be used by the developer to store additional information and use them later JavaScript side
     */
    public function __construct(
        private readonly array $points,
        private readonly ?string $title = null,
        private readonly ?InfoWindow $infoWindow = null,
        private readonly array $extra = [],
        public readonly ?string $id = null,
    ) {
    }

    /**
     * Convert the polyline to an array representation.
     *
     * @return array{
     *     points: array<array{lat: float, lng: float}>,
     *     title: string|null,
     *     infoWindow: array<string, mixed>|null,
     *     extra: array,
     *     id: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'points' => array_map(fn (Point $point) => $point->toArray(), $this->points),
            'title' => $this->title,
            'infoWindow' => $this->infoWindow?->toArray(),
            'extra' => $this->extra,
            'id' => $this->id,
        ];
    }

    /**
     * @param array{
     *     points: array<array{lat: float, lng: float}>,
     *     title: string|null,
     *     infoWindow: array<string, mixed>|null,
     *     extra: array,
     *     id: string|null
     * } $polyline
     *
     * @internal
     */
    public static function fromArray(array $polyline): self
    {
        if (!isset($polyline['points'])) {
            throw new InvalidArgumentException('The "points" parameter is required.');
        }
        $polyline['points'] = array_map(Point::fromArray(...), $polyline['points']);

        if (isset($polyline['infoWindow'])) {
            $polyline['infoWindow'] = InfoWindow::fromArray($polyline['infoWindow']);
        }

        return new self(...$polyline);
    }
}
