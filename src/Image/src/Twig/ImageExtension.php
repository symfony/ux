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
 * @author Sébastien Jean <sebastien.jean76@gmail.com>
 *
 * @internal
 */
final class ImageExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('ux_img', [ImgRuntime::class, 'renderImg'], ['is_safe' => ['html']]),
            new TwigFunction('ux_picture', [ImgRuntime::class, 'renderPicture'], ['is_safe' => ['html']]),
        ];
    }
}
