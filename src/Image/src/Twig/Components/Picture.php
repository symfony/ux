<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Twig\Components;

use Symfony\UX\Image\Renderer\ImageRendererInterface;
use Symfony\UX\Image\Twig\ImageMarkup;
use Symfony\UX\TwigComponent\ComponentAttributes;
use Twig\Markup;

/**
 * Backs the <twig:ux:picture> component.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class Picture
{
    use ImageProps;

    public function __construct(
        private readonly ImageRendererInterface $renderer,
    ) {
    }

    public function html(ComponentAttributes $attributes): Markup
    {
        $rendered = $this->renderer->renderPicture($this->src, $this->alt, $this->renderOptions());

        return new Markup(ImageMarkup::picture($rendered, $attributes), 'UTF-8');
    }
}
