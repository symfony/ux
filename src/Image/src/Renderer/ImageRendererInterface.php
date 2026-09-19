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

use Symfony\UX\Image\Exception\ExceptionInterface;
use Symfony\UX\Image\ImageAsset;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
interface ImageRendererInterface
{
    /**
     * Render an image asset into an HTML-ready structure with srcset and sizes.
     *
     * @throws ExceptionInterface
     */
    public function render(ImageAsset $asset, ?ImageRenderOptions $options = null): RenderedImage;
}
