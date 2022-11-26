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
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @final
 *
 * @experimental
 */
class BlurHashExtension extends AbstractExtension
{
    private $blurHash;

    public function __construct(BlurHashInterface $blurHash)
    {
        $this->blurHash = $blurHash;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('data_uri_thumbnail', [$this, 'createDataUriThumbnail']),
            new TwigFunction('blur_hash', [$this, 'blurHash']),
        ];
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
