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

final readonly class VariantProcessingPlan
{
    /**
     * @param list<PlannedVariant> $variants
     * @param list<string>         $formats
     */
    public function __construct(
        public array $variants,
        public array $formats,
        public int $artifactCount,
        public int $outputPixels,
    ) {
    }
}
