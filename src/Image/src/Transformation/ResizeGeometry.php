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

final class ResizeGeometry
{
    public function __construct(
        public readonly int $canvasWidth,
        public readonly int $canvasHeight,
        public readonly int $destinationX,
        public readonly int $destinationY,
        public readonly int $destinationWidth,
        public readonly int $destinationHeight,
        public readonly int $sourceX,
        public readonly int $sourceY,
        public readonly int $sourceWidth,
        public readonly int $sourceHeight,
    ) {
    }
}
