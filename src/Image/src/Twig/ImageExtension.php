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

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides `ux_image` and `ux_picture` Twig functions to render
 * responsive image elements from an ImageAsset object.
 *
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class ImageExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('ux_image', [ImageRuntime::class, 'renderImage'], ['is_safe' => ['html']]),
            new TwigFunction('ux_picture', [ImageRuntime::class, 'renderPicture'], ['is_safe' => ['html']]),
        ];
    }
}
