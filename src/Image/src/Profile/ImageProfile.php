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

final readonly class ImageProfile
{
    /**
     * @param list<string>                     $formats
     * @param array<string, VariantDefinition> $variants
     * @param array<string, mixed>             $configuration
     */
    public function __construct(
        public string $name,
        public array $formats,
        public array $variants,
        public ProcessingMode $processing,
        public array $configuration,
    ) {
    }

    public function revision(): string
    {
        return hash('sha256', json_encode($this->configuration, \JSON_THROW_ON_ERROR));
    }
}
