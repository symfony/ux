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

use Symfony\UX\Image\ImageAsset;
use Symfony\UX\Image\Renderer\ImageRendererInterface;
use Symfony\UX\Image\Renderer\ImageRenderOptions;
use Symfony\UX\Image\Renderer\RenderedImage;

/**
 * Runtime helpers used by the ux_image Twig component.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class ImageRuntime
{
    public function __construct(private ImageRendererInterface $renderer)
    {
    }

    public function render(ImageAsset $asset, ?ImageRenderOptions $options = null): RenderedImage
    {
        return $this->renderer->render($asset, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function renderImage(ImageAsset $asset, array $options = []): string
    {
        return $this->renderConfigured($asset, $options)->toImgHtml();
    }

    /**
     * @param array<string, mixed> $options
     */
    public function renderPicture(ImageAsset $asset, array $options = []): string
    {
        return $this->renderConfigured($asset, $options)->toHtml();
    }

    /**
     * @param array<string, mixed> $options
     */
    public function renderConfigured(ImageAsset $asset, array $options = []): RenderedImage
    {
        $sizes = $options['sizes'] ?? null;
        $alt = $options['alt'] ?? '';
        $lazy = $options['lazy'] ?? true;
        $fetchPriority = $options['fetchpriority'] ?? 'auto';
        $class = $options['class'] ?? '';
        $decoding = $options['decoding'] ?? 'async';
        $variant = $options['variant'] ?? null;
        $srcset = $options['srcset'] ?? null;
        $attributes = $options['attributes'] ?? [];

        return $this->render($asset, new ImageRenderOptions(
            sizes: \is_string($sizes) ? $sizes : null,
            alt: \is_string($alt) ? $alt : '',
            lazy: \is_bool($lazy) ? $lazy : true,
            fetchPriority: \is_string($fetchPriority) ? $fetchPriority : 'auto',
            class: \is_string($class) ? $class : '',
            decoding: \is_string($decoding) ? $decoding : 'async',
            variant: \is_string($variant) ? $variant : null,
            srcset: \is_array($srcset) ? $srcset : null,
            attributes: \is_array($attributes) ? $attributes : [],
        ));
    }

    /**
     * Build sources for <picture> element based on ImageAsset variants.
     *
     * @return array<int, array{type: string, srcset: string, media?: string}>
     */
    public function getSources(ImageAsset $asset): array
    {
        return $this->render($asset)->sources;
    }

    /**
     * Returns the fallback src URL using the first jpeg variant or the asset path.
     */
    public function getFallbackSrc(ImageAsset $asset): string
    {
        return $this->render($asset)->fallbackSrc;
    }

    /**
     * Returns srcset string for the fallback <img> tag.
     */
    public function getFallbackSrcset(ImageAsset $asset): ?string
    {
        return $this->render($asset)->fallbackSrcset;
    }

    public function getWidth(ImageAsset $asset): ?int
    {
        return $this->render($asset)->width;
    }

    public function getHeight(ImageAsset $asset): ?int
    {
        return $this->render($asset)->height;
    }
}
