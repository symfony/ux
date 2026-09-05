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

final class VariantProcessingPlan
{
    /**
     * @param list<PlannedVariant> $variants
     * @param list<string>         $formats
     */
    public function __construct(
        public readonly array $variants,
        public readonly array $formats,
        public readonly int $artifactCount,
        public readonly int $outputPixels,
    ) {
    }
}
