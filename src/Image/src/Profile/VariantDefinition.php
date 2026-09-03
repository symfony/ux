<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Profile;

use Symfony\UX\Image\Transformation\FocalPoint;
use Symfony\UX\Image\Transformation\ResizeMode;

final class VariantDefinition
{
    public function __construct(
        public readonly string $name,
        public readonly ?int $width,
        public readonly ?int $height,
        public readonly ResizeMode $mode,
        public readonly int $quality,
        public readonly FocalPoint $focalPoint,
        public readonly ?string $media = null,
        public readonly ?string $density = null,
    ) {
        if ('' === $name || (null === $width && null === $height)) {
            throw new \Symfony\UX\Image\Exception\InvalidArgumentException('A named image variant requires at least one dimension.');
        }
    }
}
