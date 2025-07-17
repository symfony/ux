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

use Symfony\UX\LazyImage\BlurHash\BlurHashInterface;
use Twig\Extension\RuntimeExtensionInterface;

trigger_deprecation('symfony/ux-lazy-image', '2.27.0', 'The package is deprecated and will be removed in 3.0.');

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class BlurHashRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private BlurHashInterface $blurHash,
    ) {
    }

    public function createDataUriThumbnail(string $filename, int $width, int $height, int $encodingWidth = 75, int $encodingHeight = 75): string
    {
        return $this->blurHash->createDataUriThumbnail($filename, $width, $height, $encodingWidth, $encodingHeight);
    }

    public function blurHash(string $filename, int $encodingWidth = 75, int $encodingHeight = 75): string
    {
        return $this->blurHash->encode($filename, $encodingWidth, $encodingHeight);
    }
}
