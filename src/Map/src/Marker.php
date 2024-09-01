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
 * Represents a marker on a map.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final readonly class Marker
{
    /**
     * @param array<string, mixed> $extra Extra data, can be used by the developer to store additional information and
     *                                    use them later JavaScript side
     */
    public function __construct(
        private Point $position,
        private ?string $title = null,
        private ?InfoWindow $infoWindow = null,
        private array $extra = [],
    ) {
    }

    /**
     * @return array{
     *     position: array{lat: float, lng: float},
     *     title: string|null,
     *     infoWindow: array<string, mixed>|null,
     *     extra: object,
     * }
     */
    public function toArray(): array
    {
        return [
            'position' => $this->position->toArray(),
            'title' => $this->title,
            'infoWindow' => $this->infoWindow?->toArray(),
            'extra' => (object) $this->extra,
        ];
    }

    /**
     * @param array{
     *     position: array{lat: float, lng: float},
     *     title: string|null,
     *     infoWindow: array<string, mixed>|null,
     *     extra: object,
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

        return new self(...$marker);
    }
}
