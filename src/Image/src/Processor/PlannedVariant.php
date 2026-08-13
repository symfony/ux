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

final readonly class PlannedVariant
{
    public function __construct(
        public string $name,
        public int $width,
        public int $height,
        public string $mode,
        public string $position,
        public int $quality,
        public ?string $media,
        public ?string $density,
        public ResizeGeometry $geometry,
    ) {
    }
}
