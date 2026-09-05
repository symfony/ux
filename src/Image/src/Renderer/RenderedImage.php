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

use Twig\Extra\Html\HtmlAttr\InlineStyle;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class RenderedImage
{
    /**
     * @param list<array{type: string, srcset: string}> $sources       empty means a plain <img>
     * @param array<string, string|InlineStyle>         $imgAttributes
     */
    public function __construct(
        public readonly array $sources,
        public readonly array $imgAttributes,
    ) {
    }
}
