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
interface ImageRendererInterface
{
    /**
     * Renders a single <img>, in the format the provider negotiates, or in the last configured format.
     */
    public function render(string $src, string $alt, RenderOptions $options): RenderedImage;

    /**
     * Renders a <picture> with one <source> per configured format, whatever the provider.
     */
    public function renderPicture(string $src, string $alt, RenderOptions $options): RenderedImage;
}
