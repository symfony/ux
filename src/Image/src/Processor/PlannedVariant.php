<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Processor;

use Symfony\UX\Image\Transformation\ResizeGeometry;

final class PlannedVariant
{
    public function __construct(
        public readonly string $name,
        public readonly int $width,
        public readonly int $height,
        public readonly string $mode,
        public readonly string $position,
        public readonly int $quality,
        public readonly ?string $media,
        public readonly ?string $density,
        public readonly ResizeGeometry $geometry,
    ) {
    }
}
