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
 * Represents a geographical point.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final readonly class Point
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {
        if ($latitude < -90 || $latitude > 90) {
            throw new InvalidArgumentException(\sprintf('Latitude must be between -90 and 90 degrees, "%s" given.', $latitude));
        }

        if ($longitude < -180 || $longitude > 180) {
            throw new InvalidArgumentException(\sprintf('Longitude must be between -180 and 180 degrees, "%s" given.', $longitude));
        }
    }

    /**
     * @param array{0: float, 1: float} $center
     *
     * @return self
     */
    public static function fromArray(array $center)
    {
        return new self($center[0], $center[1]);
    }

    /**
     * @return array{lat: float, lng: float}
     */
    public function toArray(): array
    {
        return [
            'lat' => $this->latitude,
            'lng' => $this->longitude,
        ];
    }
}
