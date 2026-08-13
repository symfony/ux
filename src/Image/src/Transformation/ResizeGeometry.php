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

final readonly class ResizeGeometry
{
    public function __construct(
        public int $canvasWidth,
        public int $canvasHeight,
        public int $destinationX,
        public int $destinationY,
        public int $destinationWidth,
        public int $destinationHeight,
        public int $sourceX,
        public int $sourceY,
        public int $sourceWidth,
        public int $sourceHeight,
    ) {
    }
}
