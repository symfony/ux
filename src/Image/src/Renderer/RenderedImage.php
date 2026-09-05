<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Renderer;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class RenderedImage
{
    /**
     * @param list<array{type: string, srcset: string}>   $sources       the <source> elements of a <picture>
     * @param array<string, string|array<string, string>> $imgAttributes the <img> attributes; "style" maps CSS properties to values
     */
    public function __construct(
        public readonly array $sources,
        public readonly array $imgAttributes,
    ) {
    }
}
