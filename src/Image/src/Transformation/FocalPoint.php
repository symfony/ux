<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Transformation;

final class FocalPoint
{
    public function __construct(public float $x = 0.5, public float $y = 0.5)
    {
        if ($x < 0 || $x > 1 || $y < 0 || $y > 1) {
            throw new \Symfony\UX\Image\Exception\InvalidArgumentException('A focal point must be between 0 and 1 on both axes.');
        }
    }

    public static function fromString(string $position): self
    {
        return match ($position) {
            'top' => new self(0.5, 0.0),
            'bottom' => new self(0.5, 1.0),
            'left' => new self(0.0, 0.5),
            'right' => new self(1.0, 0.5),
            'center' => new self(),
            default => self::fromPercentages($position),
        };
    }

    private static function fromPercentages(string $position): self
    {
        if (1 !== preg_match('/^(\\d{1,3}(?:\\.\\d+)?)%\\s+(\\d{1,3}(?:\\.\\d+)?)%$/', trim($position), $matches)) {
            throw new \Symfony\UX\Image\Exception\InvalidArgumentException(\sprintf('Invalid focal point "%s".', $position));
        }

        return new self((float) $matches[1] / 100, (float) $matches[2] / 100);
    }
}
