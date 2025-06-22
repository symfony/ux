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
 * Represents a rectangle on a map defined by two points: the south-west and north-east corners.
 *
 * @author Valmont Pehaut-Pietri <valmont.pehaut-pietri@proton.me>
 */
final class Rectangle implements Element
{
    public function __construct(
        public readonly Point $southWest,
        public readonly Point $northEast,
        public readonly ?string $title = null,
        public readonly ?InfoWindow $infoWindow = null,
        public readonly array $extra = [],
        public readonly ?string $id = null,
    ) {
    }

    /**
     * Convert the rectangle to an array representation.
     *
     * @return array{
     *     southWest: array{lat: float, lng: float},
     *     northEast: array{lat: float, lng: float},
     *     title: string|null,
     *     infoWindow: array<string, mixed>|null,
     *     extra: array,
     *     id: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'southWest' => $this->southWest->toArray(),
            'northEast' => $this->northEast->toArray(),
            'title' => $this->title,
            'infoWindow' => $this->infoWindow?->toArray(),
            'extra' => $this->extra,
            'id' => $this->id,
        ];
    }

    /**
     * @param array{
     *     southWest?: array{lat: float, lng: float},
     *     northEast?: array{lat: float, lng: float},
     *     title?: string|null,
     *     infoWindow?: array<string, mixed>|null,
     *     extra?: array,
     *     id?: string|null
     * } $rectangle
     *
     * @internal
     */
    public static function fromArray(array $rectangle): self
    {
        if (!isset($rectangle['southWest'])) {
            throw new InvalidArgumentException('The "southWest" point is required to create a Rectangle.');
        }

        $rectangle['southWest'] = Point::fromArray($rectangle['southWest']);

        if (!isset($rectangle['northEast'])) {
            throw new InvalidArgumentException('The "northEast" point is required to create a Rectangle.');
        }

        $rectangle['northEast'] = Point::fromArray($rectangle['northEast']);

        if (isset($rectangle['infoWindow'])) {
            $rectangle['infoWindow'] = InfoWindow::fromArray($rectangle['infoWindow']);
        }

        return new self(...$rectangle);
    }
}
