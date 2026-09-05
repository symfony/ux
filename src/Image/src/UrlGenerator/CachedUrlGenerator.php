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
        $item = null;

        try {
            $item = $this->cache->getItem($cacheKey);

            if ($item->isHit()) {
                $cached = $item->get();
                if (\is_string($cached)) {
                    return $cached;
                }
            }
        } catch (\Throwable) {
            $item = null;
        }

        $url = $this->decorated->generateAssetUrl($asset);

        try {
            if (null === $item) {
                return $url;
            }
            $item->set($url);
            $item->expiresAfter($this->ttl);
            $this->cache->save($item);
        } catch (\Throwable) {
        }

        return $url;
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
        $item = null;

        try {
            $item = $this->cache->getItem($cacheKey);

            if ($item->isHit()) {
                $cached = $item->get();
                if (\is_string($cached)) {
                    return $cached;
                }
            }
        } catch (\Throwable) {
            $item = null;
        }

        $url = $this->decorated->generateVariantUrl($asset, $variant);

        try {
            if (null === $item) {
                return $url;
            }
            $item->set($url);
            $item->expiresAfter($this->ttl);
            $this->cache->save($item);
        } catch (\Throwable) {
        }

        return $url;
    }

    private function getCacheKey(string $type, string $storageName, string $path): string
    {
        return \sprintf('ux_image.url.%s.%s', $type, hash('sha256', $this->namespace."\0".$storageName."\0".$path));
    }
}
