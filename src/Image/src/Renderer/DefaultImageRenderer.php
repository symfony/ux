<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Renderer;

use Symfony\UX\Image\ImageAsset;
use Symfony\UX\Image\ImageSourceSet;
use Symfony\UX\Image\Profile\ProfileRegistry;
use Symfony\UX\Image\UrlGenerator\UrlGeneratorInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class DefaultImageRenderer implements ImageRendererInterface
{
    /** @param list<string> $preferredFormats */
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $defaultSizes = '100vw',
        private readonly array $preferredFormats = ['avif', 'webp', 'jpeg', 'jpg', 'png'],
        private readonly ?ProfileRegistry $profiles = null,
    ) {
    }

    public function render(ImageAsset $asset, ?ImageRenderOptions $options = null): RenderedImage
    {
        $profileConfiguration = $this->profileConfiguration($asset);
        $profileSizes = $profileConfiguration['sizes'] ?? null;
        $resolvedSizes = \is_string($profileSizes) ? $profileSizes : $this->defaultSizes;
        $options = null === $options
            ? new ImageRenderOptions(sizes: $resolvedSizes)
            : $this->withResolvedSizes($options, $resolvedSizes);
        $profileFormats = $profileConfiguration['preferred_formats'] ?? null;
        $preferredFormats = $this->normalizePreferredFormats($profileFormats);

        $filteredAsset = $this->filterVariants($asset, $options);
        [$width, $height] = $this->resolveDimensions($filteredAsset);

        return new RenderedImage(
            asset: $filteredAsset,
            sources: $this->buildSources($filteredAsset, $preferredFormats),
            fallbackSrc: $this->getFallbackSrc($filteredAsset),
            fallbackSrcset: null !== $options->srcset ? implode(', ', $options->srcset) : $this->getFallbackSrcset($filteredAsset),
            width: $width,
            height: $height,
            options: $options,
        );
    }

    /**
     * @param list<string> $preferredFormats
     *
     * @return list<array{type: string, srcset: string, media?: string}>
     */
    private function buildSources(ImageAsset $asset, array $preferredFormats): array
    {
        $sourceSet = $asset->getImageSourceSet();

        if ($sourceSet->isEmpty()) {
            return [];
        }

        if ($sourceSet->isMultiRatio()) {
            return $this->buildMultiRatioSources($sourceSet, $asset, $preferredFormats);
        }

        return $this->buildSingleRatioSources($sourceSet, $asset, $preferredFormats);
    }

    /**
     * @param list<string> $preferredFormats
     *
     * @return list<array{type: string, srcset: string, media?: string}>
     */
    private function buildSingleRatioSources(ImageSourceSet $sourceSet, ImageAsset $asset, array $preferredFormats): array
    {
        $sources = [];
        foreach ($this->preferredFormats($asset, $preferredFormats) as $format) {
            $variantList = $sourceSet->getForFormat($format);
            $sources[] = [
                'type' => $this->sourceMimeType($format, $variantList),
                'srcset' => $this->buildSrcset($asset, $variantList),
            ];
        }

        return $sources;
    }

    /**
     * @param list<string> $preferredFormats
     *
     * @return list<array{type: string, srcset: string, media?: string}>
     */
    private function buildMultiRatioSources(ImageSourceSet $sourceSet, ImageAsset $asset, array $preferredFormats): array
    {
        $sources = [];
        foreach ($sourceSet->getMultiRatioGroups() as $group) {
            $media = $group['media'];

            foreach ($this->sortFormats(array_keys($group['formats']), $preferredFormats) as $format) {
                $variantList = $group['formats'][$format];
                $source = [
                    'type' => $this->sourceMimeType($format, $variantList),
                    'srcset' => $this->buildSrcset($asset, $variantList),
                ];
                if ($media) {
                    $source['media'] = $media;
                }
                $sources[] = $source;
            }
        }

        return $sources;
    }

    /**
     * @param list<string> $preferredFormats
     *
     * @return list<string>
     */
    private function preferredFormats(ImageAsset $asset, array $preferredFormats): array
    {
        return $this->sortFormats($asset->getAvailableFormats(), $preferredFormats);
    }

    /**
     * @param list<string> $formats
     * @param list<string> $preferredOrder
     *
     * @return list<string>
     */
    private function sortFormats(array $formats, array $preferredOrder): array
    {
        usort($formats, static function (string $a, string $b) use ($preferredOrder): int {
            $indexA = array_search($a, $preferredOrder, true);
            $indexB = array_search($b, $preferredOrder, true);

            return (false === $indexA ? \PHP_INT_MAX : $indexA) <=> (false === $indexB ? \PHP_INT_MAX : $indexB);
        });

        return $formats;
    }

    private function getFallbackSrc(ImageAsset $asset): string
    {
        $format = $this->fallbackFormat($asset);

        if (null === $format) {
            return $this->urlGenerator->generateAssetUrl($asset);
        }

        $primaryVariant = $asset->getPrimaryVariantForFormat($format);

        return match (true) {
            $primaryVariant && isset($primaryVariant['path']) => $this->urlGenerator->generateVariantUrl($asset, $primaryVariant),
            default => $this->urlGenerator->generateAssetUrl($asset),
        };
    }

    private function getFallbackSrcset(ImageAsset $asset): ?string
    {
        $format = $this->fallbackFormat($asset);

        if (!$format) {
            return null;
        }

        $variants = $asset->getImageSourceSet()->getForFormat($format);
        $fallbackVariants = array_values(array_filter($variants, static fn ($variant): bool => null === $variant->media));
        $parts = $this->buildSrcset($asset, [] !== $fallbackVariants ? $fallbackVariants : $variants);

        return '' !== $parts ? $parts : null;
    }

    /**
     * @param list<\Symfony\UX\Image\ImageSource> $variants
     *
     * A srcset may use width or density descriptors, never both. When every
     * candidate has a density, density wins. Otherwise the canonical persisted
     * widths are used. Duplicate descriptors collapse to the last configured
     * candidate, which preserves named variants while emitting valid HTML.
     */
    private function buildSrcset(ImageAsset $asset, array $variants): string
    {
        $allDensity = [] !== $variants;
        $allWidth = [] !== $variants;
        foreach ($variants as $variant) {
            $allDensity = $allDensity && null !== $variant->density;
            $allWidth = $allWidth && null !== $variant->width;
        }

        $entries = [];
        foreach ($variants as $index => $variant) {
            $url = $this->urlGenerator->generateVariantUrl($asset, $variant->toArray());
            if ($allDensity) {
                \assert(null !== $variant->density);
                $entries[$variant->density] = \sprintf('%s %s', $url, $variant->density);
            } elseif ($allWidth) {
                \assert(null !== $variant->width);
                $entries[$variant->width.'w'] = \sprintf('%s %dw', $url, $variant->width);
            } elseif (0 === $index) {
                $entries['bare'] = $url;
            }
        }

        return implode(', ', $entries);
    }

    /** @param list<\Symfony\UX\Image\ImageSource> $variants */
    private function sourceMimeType(string $format, array $variants): string
    {
        if (isset($variants[0]) && null !== $variants[0]->mimeType) {
            return $variants[0]->mimeType;
        }

        return match ($format) {
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'image/'.$format,
        };
    }

    /**
     * @return array{?int, ?int}
     */
    private function resolveDimensions(ImageAsset $asset): array
    {
        if ($asset->width || $asset->height) {
            return [$asset->width, $asset->height];
        }

        $format = $asset->getDefaultFormat();
        $variant = $format ? $asset->getPrimaryVariantForFormat($format) : null;

        if (null !== $variant) {
            $w = $variant['width'] ?? null;
            $h = $variant['height'] ?? null;

            return [
                \is_int($w) || \is_float($w) ? (int) $w : null,
                \is_int($h) || \is_float($h) ? (int) $h : null,
            ];
        }

        // Rendering is deliberately metadata-only. Assets without dimensions
        // remain renderable, but no filesystem or network I/O occurs.
        return [null, null];
    }

    private function fallbackFormat(ImageAsset $asset): ?string
    {
        $formats = $asset->getAvailableFormats();
        foreach (['jpeg', 'jpg', 'png'] as $format) {
            if (\in_array($format, $formats, true)) {
                return $format;
            }
        }

        return null;
    }

    private function filterVariants(ImageAsset $asset, ImageRenderOptions $options): ImageAsset
    {
        if (null === $options->variant) {
            return $asset;
        }

        if (!$asset->variants) {
            throw new \Symfony\UX\Image\Exception\InvalidArgumentException(\sprintf('The image variant "%s" does not exist.', $options->variant));
        }

        $filtered = [];

        foreach ($asset->variants as $format => $variantList) {
            $kept = array_values(array_filter(
                $variantList,
                static fn (array $v): bool => ($v['name'] ?? null) === $options->variant,
            ));

            if ($kept) {
                $filtered[$format] = $kept;
            }
        }

        if ([] === $filtered) {
            throw new \Symfony\UX\Image\Exception\InvalidArgumentException(\sprintf('The image variant "%s" does not exist.', $options->variant));
        }

        return new ImageAsset(
            $asset->storageName,
            $asset->path,
            $asset->originalFilename,
            $asset->mimeType,
            null,
            null,
            $filtered,
            $asset->schemaVersion,
            $asset->profile,
            $asset->profileRevision,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function profileConfiguration(ImageAsset $asset): array
    {
        if (null === $asset->profile || null === $this->profiles || !$this->profiles->has($asset->profile)) {
            return [];
        }

        return $this->profiles->get($asset->profile)->configuration;
    }

    /**
     * @return list<string>
     */
    private function normalizePreferredFormats(mixed $formats): array
    {
        if (!\is_array($formats)) {
            return $this->preferredFormats;
        }

        $normalized = array_values(array_filter($formats, 'is_string'));

        return [] !== $normalized ? $normalized : $this->preferredFormats;
    }

    private function withResolvedSizes(ImageRenderOptions $options, string $default): ImageRenderOptions
    {
        if (null !== $options->sizes) {
            return $options;
        }

        return new ImageRenderOptions(
            sizes: $default,
            alt: $options->alt,
            lazy: $options->lazy,
            fetchPriority: $options->fetchPriority,
            class: $options->class,
            decoding: $options->decoding,
            variant: $options->variant,
            srcset: $options->srcset,
            attributes: $options->attributes,
        );
    }
}
