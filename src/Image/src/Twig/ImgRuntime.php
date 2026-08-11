<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Twig;

use Symfony\UX\Image\ImageRenderer;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * @author Sébastien Jean <sebastien.jean76@gmail.com>
 *
 * @internal
 */
final class ImgRuntime implements RuntimeExtensionInterface
{
    private const IMG_PROPS = ['src', 'alt', 'srcset', 'sizes', 'width', 'height', 'loading', 'decoding', 'fetchpriority', 'crossorigin', 'referrerpolicy', 'ismap', 'usemap'];

    public function __construct(
        private readonly ImageRenderer $renderer,
    ) {
    }

    public function renderImg(
        ?string $src = null,
        ?string $alt = null,
        string|array|null $srcset = null,
        string|array|null $sizes = null,
        ?int $width = null,
        ?int $height = null,
        ?string $loading = null,
        ?string $decoding = null,
        ?string $fetchpriority = null,
        ?string $crossorigin = null,
        ?string $referrerpolicy = null,
        ?bool $ismap = null,
        ?string $usemap = null,
        array $attributes = [],
    ): string {
        return $this->renderer->renderImg($src, $alt, $srcset, $sizes, $width, $height, $loading, $decoding, $fetchpriority, $crossorigin, $referrerpolicy, $ismap, $usemap, $attributes);
    }

    public function renderPicture(
        array $sources,
        ?string $src = null,
        ?string $alt = null,
        string|array|null $srcset = null,
        string|array|null $sizes = null,
        ?int $width = null,
        ?int $height = null,
        ?string $loading = null,
        ?string $decoding = null,
        array $attributes = [],
        array $imgAttributes = [],
    ): string {
        return $this->renderer->renderPicture($sources, $src, $alt, $srcset, $sizes, $width, $height, $loading, $decoding, $attributes, $imgAttributes);
    }

    public function render(array $args = []): string
    {
        $props = array_intersect_key($args, array_flip(self::IMG_PROPS));
        $attrs = array_diff_key($args, array_flip(self::IMG_PROPS));

        return $this->renderImg(
            src: $props['src'] ?? null,
            alt: $props['alt'] ?? null,
            srcset: $props['srcset'] ?? null,
            sizes: $props['sizes'] ?? null,
            width: $props['width'] ?? null,
            height: $props['height'] ?? null,
            loading: $props['loading'] ?? null,
            decoding: $props['decoding'] ?? null,
            fetchpriority: $props['fetchpriority'] ?? null,
            crossorigin: $props['crossorigin'] ?? null,
            referrerpolicy: $props['referrerpolicy'] ?? null,
            ismap: $props['ismap'] ?? null,
            usemap: $props['usemap'] ?? null,
            attributes: $attrs,
        );
    }
}
