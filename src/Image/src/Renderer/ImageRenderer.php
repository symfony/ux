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

use Symfony\UX\Image\Exception\InvalidArgumentException;
use Symfony\UX\Image\Exception\LogicException;
use Symfony\UX\Image\ImageTransformation;
use Symfony\UX\Image\Provider\ProviderInterface;
use Twig\Extra\Html\HtmlAttr\InlineStyle;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class ImageRenderer implements ImageRendererInterface
{
    /**
     * @param list<string> $formats
     */
    public function __construct(
        private readonly ProviderInterface $provider,
        private readonly LayoutResolver $layoutResolver,
        private readonly array $formats = ['avif', 'webp', 'jpeg'],
    ) {
    }

    public function render(string $src, string $alt, RenderOptions $options): RenderedImage
    {
        $operations = $this->resolveOperations($options->operations);
        $breakpoints = $options->breakpoints ?? $this->layoutResolver->breakpoints($options->layout, $options->width);
        $ratio = $this->resolveRatio($options);

        $pinned = $options->format;
        if (null !== $pinned) {
            $this->assertSupportedFormat($pinned);
        }

        $auto = null === $pinned && $this->provider->supportsAutoFormat();
        $formats = match (true) {
            null !== $pinned => [$pinned],
            $auto => ['auto'],
            default => $this->resolveFormats(),
        };

        $sources = [];
        if (null === $pinned && !$auto) {
            foreach ($formats as $format) {
                $sources[] = [
                    'type' => 'image/'.$format,
                    'srcset' => $this->buildSrcset($src, $breakpoints, $format, $options, $operations, $ratio),
                ];
            }
        }

        $fallbackFormat = $formats[\count($formats) - 1];
        // The loop above already built this exact srcset as the last $sources entry when it ran; reuse it instead of calling generateUrl() again for every breakpoint.
        $fallbackSrcset = [] !== $sources ? $sources[\count($sources) - 1]['srcset'] : $this->buildSrcset($src, $breakpoints, $fallbackFormat, $options, $operations, $ratio);

        $attributes = [
            'src' => $this->provider->generateUrl(
                // "src" must match the srcset candidates for bots/crawlers that ignore srcset: no width fallback, no height without a known ratio.
                new ImageTransformation($src, $options->width, null !== $ratio ? $options->height : null, $options->fit, $fallbackFormat, $options->quality, $operations),
            ),
            'alt' => $alt,
            'srcset' => $fallbackSrcset,
            'loading' => $options->priority ? 'eager' : 'lazy',
            'fetchpriority' => $options->priority ? 'high' : 'auto',
            'decoding' => 'async',
        ];

        if (null !== $sizes = $this->layoutResolver->sizes($options->layout, $options->width)) {
            $attributes['sizes'] = $sizes;
        }
        if (null !== $options->width) {
            $attributes['width'] = (string) $options->width;
        }
        if (null !== $options->height) {
            $attributes['height'] = (string) $options->height;
        }
        $attributes['style'] = new InlineStyle($this->layoutResolver->style($options->layout, $options->width, $options->height, $options->objectFit));

        return new RenderedImage($sources, $attributes);
    }

    private function assertSupportedFormat(string $format): void
    {
        $supported = $this->provider->getSupportedFormats();

        // Empty means an unconfigured NullProvider, not "supports nothing"; the real error surfaces from generateUrl() below.
        if ([] === $supported) {
            return;
        }

        if (!\in_array($format, $supported, true)) {
            throw new InvalidArgumentException(\sprintf('The image format "%s" is not supported by the "%s" provider (supported: "%s").', $format, $this->provider->getName(), implode('", "', $supported)));
        }
    }

    /**
     * @return list<string>
     */
    private function resolveFormats(): array
    {
        $supported = $this->provider->getSupportedFormats();
        $formats = array_values(array_intersect($this->formats, $supported));

        if ([] === $formats) {
            throw new LogicException(\sprintf('None of the configured formats ("%s") are supported by the "%s" provider (supported: "%s").', implode('", "', $this->formats), $this->provider->getName(), implode('", "', $supported)));
        }

        return $formats;
    }

    /**
     * @param list<int>             $breakpoints
     * @param array<string, scalar> $operations
     */
    private function buildSrcset(string $src, array $breakpoints, string $format, RenderOptions $options, array $operations, ?float $ratio): string
    {
        // Browsers always fetch from srcset over src, so a height-less candidate would make "fit" a no-op here.
        $entries = [];
        foreach ($breakpoints as $breakpoint) {
            $height = null !== $ratio ? max(1, (int) round($breakpoint * $ratio)) : null;
            $url = $this->provider->generateUrl(
                new ImageTransformation($src, $breakpoint, $height, $options->fit, $format, $options->quality, $operations),
            );
            $entries[] = $url.' '.$breakpoint.'w';
        }

        return implode(', ', $entries);
    }

    private function resolveRatio(RenderOptions $options): ?float
    {
        return null !== $options->width && null !== $options->height ? $options->height / $options->width : null;
    }

    /**
     * @param array<string, array<string, scalar>> $operations
     *
     * @return array<string, scalar>
     */
    private function resolveOperations(array $operations): array
    {
        $resolved = $operations[$this->provider->getName()] ?? [];
        $supported = $this->provider->getSupportedOperations();

        foreach (array_keys($resolved) as $name) {
            if (!\in_array($name, $supported, true)) {
                throw new InvalidArgumentException(\sprintf('The image operation "%s" is not supported by the "%s" provider (supported: "%s").', $name, $this->provider->getName(), implode('", "', $supported)));
            }
        }

        return $resolved;
    }
}
