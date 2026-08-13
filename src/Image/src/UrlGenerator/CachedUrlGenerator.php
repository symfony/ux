<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\UrlGenerator;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\UX\Image\ImageAsset;

/**
 * Cached wrapper for URL generation to improve performance in high-traffic scenarios.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class CachedUrlGenerator implements UrlGeneratorInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $decorated,
        private readonly CacheItemPoolInterface $cache,
        private readonly int $ttl = 3600,
        private readonly string $namespace = '',
    ) {
    }

    public function generateAssetUrl(ImageAsset $asset): string
    {
        $cacheKey = $this->getCacheKey('asset', $asset->storageName, $asset->path);

        try {
            $item = $this->cache->getItem($cacheKey);

            if ($item->isHit()) {
                $cached = $item->get();
                if (\is_string($cached)) {
                    return $cached;
                }
            }

            $url = $this->decorated->generateAssetUrl($asset);

            $item->set($url);
            $item->expiresAfter($this->ttl);
            $this->cache->save($item);

            return $url;
        } catch (\Throwable) {
            // Graceful degradation: if cache fails, fall back to decorated generator
            return $this->decorated->generateAssetUrl($asset);
        }
    }

    public function generateVariantUrl(ImageAsset $asset, array $variant): string
    {
        $variantKey = hash('sha256', serialize([
            'namespace' => $this->namespace,
            'storage' => $asset->storageName,
            'assetPath' => $asset->path,
            'variant' => $variant,
        ]));
        $cacheKey = \sprintf('ux_image.url.variant.%s', $variantKey);

        try {
            $item = $this->cache->getItem($cacheKey);

            if ($item->isHit()) {
                $cached = $item->get();
                if (\is_string($cached)) {
                    return $cached;
                }
            }

            $url = $this->decorated->generateVariantUrl($asset, $variant);

            $item->set($url);
            $item->expiresAfter($this->ttl);
            $this->cache->save($item);

            return $url;
        } catch (\Throwable) {
            // Graceful degradation: if cache fails, fall back to decorated generator
            return $this->decorated->generateVariantUrl($asset, $variant);
        }
    }

    private function getCacheKey(string $type, string $storageName, string $path): string
    {
        return \sprintf('ux_image.url.%s.%s', $type, hash('sha256', $this->namespace."\0".$storageName."\0".$path));
    }
}
