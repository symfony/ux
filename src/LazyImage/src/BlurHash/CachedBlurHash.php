<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LazyImage\BlurHash;

use Symfony\Contracts\Cache\CacheInterface;

/**
 * Decorate a BlurHashInterface to cache the result of the encoding, for performance purposes.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @final
 */
class CachedBlurHash implements BlurHashInterface
{
    public function __construct(
        private BlurHashInterface $blurHash,
        private CacheInterface $cache,
    ) {
    }

    public function createDataUriThumbnail(string $filename, int $width, int $height, int $encodingWidth = 75, int $encodingHeight = 75): string
    {
        return $this->blurHash->createDataUriThumbnail($filename, $width, $height, $encodingWidth, $encodingHeight);
    }

    public function encode(string $filename, int $encodingWidth = 75, int $encodingHeight = 75): string
    {
        return $this->cache->get(
            'blurhash.'.hash('xxh3', $filename.$encodingWidth.$encodingHeight),
            fn () => $this->blurHash->encode($filename, $encodingWidth, $encodingHeight)
        );
    }
}
