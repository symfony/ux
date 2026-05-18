<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Service;

/**
 * Value object representing image transformation configuration.
 *
 * @author Aleksey Razbakov <aleksey@razbakov.com>
 */
final class ImageConfig
{
    public function __construct(
        public readonly string $src,
        public readonly ?string $alt = null,
        public readonly ?string $width = null,
        public readonly ?int $height = null,
        public readonly ?string $ratio = null,
        public readonly ?string $fit = 'cover',
        public readonly ?string $focal = 'center',
        public readonly ?int $quality = 80,
        public readonly ?string $format = 'webp',
        public readonly ?string $loading = 'lazy',
        public readonly ?string $fetchpriority = 'low',
        public readonly ?string $background = null,
        public readonly ?string $fallback = 'lg',
        public readonly ?string $fallbackFormat = 'auto',
        public readonly ?string $densities = null,
        public readonly ?string $provider = null,
        public readonly ?array $modifiers = null,
    ) {
    }

    /**
     * Returns modifiers array for the provider.
     */
    public function toModifiers(?int $widthOverride = null): array
    {
        $modifiers = [];

        if (null !== $widthOverride) {
            $modifiers['width'] = $widthOverride;
        } elseif (null !== $this->width && is_numeric($this->width)) {
            $modifiers['width'] = (int) $this->width;
        }

        if (null !== $this->height) {
            $modifiers['height'] = $this->height;
        }

        if (null !== $this->format) {
            $modifiers['format'] = $this->format;
        }

        if (null !== $this->quality) {
            $modifiers['quality'] = $this->quality;
        }

        if (null !== $this->fit) {
            $modifiers['fit'] = $this->fit;
        }

        if (null !== $this->focal) {
            $modifiers['focal'] = $this->focal;
        }

        if (null !== $this->background) {
            $modifiers['background'] = $this->background;
        }

        if (null !== $this->ratio) {
            $modifiers['ratio'] = $this->ratio;
        }

        if (null !== $this->modifiers) {
            $modifiers = array_merge($modifiers, $this->modifiers);
        }

        return $modifiers;
    }
}
