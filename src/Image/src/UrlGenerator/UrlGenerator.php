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

use Symfony\UX\Image\ImageAsset;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class UrlGenerator implements UrlGeneratorInterface
{
    /** @var array<string, CdnUrlBuilderInterface> */
    private array $cdnBuilders = [];

    /** @var array<string, UrlAdapterInterface> */
    private array $urlAdapters = [];

    /**
     * @param iterable<CdnUrlBuilderInterface>    $cdnUrlBuilders
     * @param iterable<UrlAdapterInterface>       $urlAdapters
     * @param array<string, array<string, mixed>> $storages
     */
    public function __construct(
        iterable $cdnUrlBuilders,
        iterable $urlAdapters,
        private readonly array $storages,
    ) {
        foreach ($cdnUrlBuilders as $provider => $builder) {
            if (\is_string($provider)) {
                $this->cdnBuilders[$provider] = $builder;
                continue;
            }

            $this->cdnBuilders[$builder::getProviderName()] = $builder;
        }

        foreach ($urlAdapters as $name => $adapter) {
            if (\is_string($name)) {
                $this->urlAdapters[$name] = $adapter;
                continue;
            }

            $this->urlAdapters[$adapter::getName()] = $adapter;
        }
    }

    public function generateAssetUrl(ImageAsset $asset): string
    {
        return $this->buildUrlForStorage($asset->storageName, $asset->path, []);
    }

    public function generateVariantUrl(ImageAsset $asset, array $variant): string
    {
        $variantPath = isset($variant['path']) && \is_string($variant['path']) ? $variant['path'] : $asset->path;

        return $this->buildUrlForStorage($asset->storageName, $variantPath, $variant);
    }

    /**
     * @param array<string, mixed> $variantConfig
     */
    private function buildUrlForStorage(string $storageName, string $path, array $variantConfig): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        /** @var array<string, mixed> $storage */
        $storage = $this->storages[$storageName] ?? [];

        /** @var array<string, mixed> $cdnConfig */
        $cdnConfig = \is_array($storage['cdn'] ?? null) ? $storage['cdn'] : [];
        $cdnProvider = isset($cdnConfig['provider']) && \is_string($cdnConfig['provider']) ? $cdnConfig['provider'] : null;

        if (null !== $cdnProvider) {
            if (!isset($this->cdnBuilders[$cdnProvider])) {
                throw new \Symfony\UX\Image\Exception\InvalidArgumentException(\sprintf('Unknown CDN URL builder "%s". Make sure it is tagged as "ux_image.cdn_url_builder" with the matching provider.', $cdnProvider));
            }
            $baseUrl = isset($cdnConfig['base_url']) && \is_string($cdnConfig['base_url']) ? $cdnConfig['base_url'] : '';

            return $this->cdnBuilders[$cdnProvider]->buildUrl($baseUrl, $path, $storage, $variantConfig);
        }

        $adapterName = isset($storage['url_adapter']) && \is_string($storage['url_adapter']) ? $storage['url_adapter'] : GenericUrlAdapter::getName();
        if (!isset($this->urlAdapters[$adapterName])) {
            throw new \Symfony\UX\Image\Exception\InvalidArgumentException(\sprintf('Unknown URL adapter "%s". Make sure it is configured and tagged as "ux_image.storage_adapter".', $adapterName));
        }
        $adapter = $this->urlAdapters[$adapterName];

        return $adapter->resolve($path, $storage, $variantConfig, $storageName);
    }
}
