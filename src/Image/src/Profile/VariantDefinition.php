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

final readonly class VariantDefinition
{
    public function __construct(
        public string $name,
        public ?int $width,
        public ?int $height,
        public ResizeMode $mode,
        public int $quality,
        public FocalPoint $focalPoint,
        public ?string $media = null,
        public ?string $density = null,
    ) {
        if ('' === $name || (null === $width && null === $height)) {
            throw new \Symfony\UX\Image\Exception\InvalidArgumentException('A named image variant requires at least one dimension.');
        }
    }
}
