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

/**
 * Represents a marker on a map.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final readonly class Marker
{
    public static function fromArray(
        array $position,
        ?string $title = null,
        ?array $infoWindow = null,
        array $extra = [],
    ): self {
        return new self(
            Point::fromArray($position),
            $title,
            $infoWindow ? InfoWindow::fromArray(...$infoWindow) : null,
            $extra,
        );
    }

    /**
     * @param array<string, mixed> $extra Extra data, can be used by the developer to store additional information and use them later JavaScript side
     */
    public function __construct(
        private Point $position,
        private ?string $title = null,
        private ?InfoWindow $infoWindow = null,
        private array $extra = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'position' => $this->position->toArray(),
            'title' => $this->title,
            'infoWindow' => $this->infoWindow?->toArray(),
            'extra' => (object) $this->extra,
        ];
    }
}
