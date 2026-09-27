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

use Symfony\UX\Image\Exception\LogicException;
use Symfony\UX\Image\ImageUrlGenerator;
use Symfony\UX\Image\Provider\NullProvider;
use Symfony\UX\Image\Provider\ProviderInterface;
use Twig\Extra\Html\HtmlAttr\InlineStyle;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class ImageRenderer implements ImageRendererInterface
{
    private readonly ImageUrlGenerator $urlGenerator;

    /**
     * @param list<string> $formats
     */
    public function __construct(
        private readonly ProviderInterface $provider,
        private readonly LayoutResolver $layoutResolver,
        private readonly array $formats = ['avif', 'webp', 'jpeg'],
    ) {
        $this->urlGenerator = new ImageUrlGenerator($provider);
    }

    public function render(string $src, string $alt, RenderOptions $options): RenderedImage
    {
        if ($this->provider instanceof NullProvider) {
            return new RenderedImage([], ['src' => $src, 'alt' => $alt] + $this->commonAttributes($options));
        }

        $breakpoints = $options->breakpoints ?? $this->layoutResolver->breakpoints($options->layout, $options->width);
        $ratio = $this->resolveRatio($options);

        $pinned = $options->format;
        $auto = null === $pinned && $this->provider->supportsAutoFormat();
        $formats = match (true) {
            null !== $pinned => [$pinned],
            $auto => ['auto'],
            default => $this->resolveFormats(),
        };

        $sources = [];
        $fallbackSrcset = null;
        if (null === $pinned && !$auto) {
            foreach ($formats as $format) {
                $fallbackSrcset = $this->buildSrcset($src, $breakpoints, $format, $options, $ratio);
                $sources[] = [
                    'type' => 'image/'.$format,
                    'srcset' => $fallbackSrcset,
                ];
            }
        }

        $fallbackFormat = $formats[\count($formats) - 1];
        $fallbackSrcset ??= $this->buildSrcset($src, $breakpoints, $fallbackFormat, $options, $ratio);

        $attributes = [
            // "src" must match the srcset candidates for bots/crawlers that ignore srcset: no width fallback, no height without a known ratio.
            'src' => $this->urlGenerator->generate($src, $options->width, null !== $ratio ? $options->height : null, $options->fit, $fallbackFormat, $options->quality, $options->operations),
            'alt' => $alt,
            'srcset' => $fallbackSrcset,
        ];

        if (null !== $sizes = $this->layoutResolver->sizes($options->layout, $options->width)) {
            $attributes['sizes'] = $sizes;
        }

        return new RenderedImage($sources, $attributes + $this->commonAttributes($options));
    }

    /**
     * @return array<string, string|InlineStyle>
     */
    private function commonAttributes(RenderOptions $options): array
    {
        $attributes = ['loading' => $options->priority ? 'eager' : 'lazy'];

        if ($options->priority) {
            $attributes['fetchpriority'] = 'high';
        }
        if (null !== $options->width) {
            $attributes['width'] = (string) $options->width;
        }
        if (null !== $options->height) {
            $attributes['height'] = (string) $options->height;
        }
        $attributes['style'] = new InlineStyle($this->layoutResolver->style($options->layout, $options->width, $options->height, $options->objectFit ?? $options->fit?->value ?? 'cover'));

        return $attributes;
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
     * @param list<int> $breakpoints
     */
    private function buildSrcset(string $src, array $breakpoints, string $format, RenderOptions $options, ?float $ratio): string
    {
        // Browsers always fetch from srcset over src, so a height-less candidate would make "fit" a no-op here.
        $entries = [];
        foreach ($breakpoints as $breakpoint) {
            $height = null !== $ratio ? max(1, (int) round($breakpoint * $ratio)) : null;
            $url = $this->urlGenerator->generate($src, $breakpoint, $height, $options->fit, $format, $options->quality, $options->operations);
            $entries[] = $url.' '.$breakpoint.'w';
        }

        return implode(', ', $entries);
    }

    private function resolveRatio(RenderOptions $options): ?float
    {
        return null !== $options->width && null !== $options->height ? $options->height / $options->width : null;
    }
}
