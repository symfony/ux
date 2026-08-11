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

/**
 * @author Sébastien Jean <sebastien.jean76@gmail.com>
 *
 * @internal
 */
final class SourceRuntime
{
    private const SOURCE_PROPS = ['srcset', 'sizes', 'media', 'type', 'width', 'height'];

    public function __construct(
        private readonly ImageRenderer $renderer,
    ) {
    }

    public function render(array $args = []): string
    {
        $props = array_intersect_key($args, array_flip(self::SOURCE_PROPS));
        $attrs = array_diff_key($args, array_flip(self::SOURCE_PROPS));

        return $this->renderer->renderSource(
            srcset: $props['srcset'] ?? '',
            sizes: $props['sizes'] ?? null,
            media: $props['media'] ?? null,
            type: $props['type'] ?? null,
            width: $props['width'] ?? null,
            height: $props['height'] ?? null,
            attributes: $attrs,
        );
    }
}
