<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LazyImage\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @final
 */
class BlurHashExtension extends AbstractExtension
{
    public function getFunctions(): iterable
    {
        yield new TwigFunction('data_uri_thumbnail', [BlurHashRuntime::class, 'createDataUriThumbnail']);
        yield new TwigFunction('blur_hash', [BlurHashRuntime::class, 'blurHash']);
    }
}
