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

/**
 * Value object capable of rendering itself as an HTML <picture> element.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final readonly class RenderedImage
{
    /**
     * @param array<int, array{type: string, srcset: string, media?: string}> $sources
     */
    public function __construct(
        public ImageAsset $asset,
        public array $sources,
        public string $fallbackSrc,
        public ?string $fallbackSrcset,
        public ?int $width,
        public ?int $height,
        public ImageRenderOptions $options,
    ) {
    }

    public function getAsset(): ImageAsset
    {
        return $this->asset;
    }

    /**
     * @return array<int, array{type: string, srcset: string, media?: string}>
     */
    public function getSources(): array
    {
        return $this->sources;
    }

    public function getFallbackSrc(): string
    {
        return $this->fallbackSrc;
    }

    public function getFallbackSrcset(): ?string
    {
        return $this->fallbackSrcset;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function getOptions(): ImageRenderOptions
    {
        return $this->options;
    }

    public function __toString(): string
    {
        return $this->toHtml();
    }

    public function toHtml(): string
    {
        $sizes = null !== $this->options->sizes && '' !== $this->options->sizes
            ? \sprintf(' sizes="%s"', htmlspecialchars($this->options->sizes, \ENT_QUOTES))
            : '';
        $sourceTags = array_map(
            static fn (array $source): string => \sprintf(
                '<source type="%s" srcset="%s"%s%s />',
                htmlspecialchars($source['type'], \ENT_QUOTES),
                htmlspecialchars($source['srcset'], \ENT_QUOTES),
                $sizes,
                isset($source['media']) ? \sprintf(' media="%s"', htmlspecialchars($source['media'], \ENT_QUOTES)) : ''
            ),
            $this->sources,
        );

        $attributes = [\sprintf('src="%s"', htmlspecialchars($this->fallbackSrc, \ENT_QUOTES))];

        if ('' !== $sizes) {
            $attributes[] = ltrim($sizes);
        }

        $attributes = [
            ...$attributes,
            \sprintf('alt="%s"', htmlspecialchars($this->options->alt, \ENT_QUOTES)),
            \sprintf('loading="%s"', $this->loadingAttribute()),
            \sprintf('fetchpriority="%s"', $this->fetchPriorityAttribute()),
            \sprintf('decoding="%s"', htmlspecialchars($this->options->decoding, \ENT_QUOTES)),
        ];

        if ($this->fallbackSrcset) {
            $attributes[] = \sprintf('srcset="%s"', htmlspecialchars($this->fallbackSrcset, \ENT_QUOTES));
        }

        if ($this->width) {
            $attributes[] = \sprintf('width="%d"', $this->width);
        }

        if ($this->height) {
            $attributes[] = \sprintf('height="%d"', $this->height);
        }

        if ('' !== $this->options->class) {
            $attributes[] = \sprintf('class="%s"', htmlspecialchars($this->options->class, \ENT_QUOTES));
        }
        $attributes = [...$attributes, ...$this->customAttributes()];

        $picture = '<picture>';
        if ($sourceTags) {
            $picture .= implode('', $sourceTags);
        }

        $picture .= \sprintf('<img %s />', implode(' ', $attributes));
        $picture .= '</picture>';

        return $picture;
    }

    public function toPictureHtml(): string
    {
        return $this->toHtml();
    }

    public function toImgHtml(): string
    {
        $attributes = [
            \sprintf('src="%s"', htmlspecialchars($this->fallbackSrc, \ENT_QUOTES)),
            \sprintf('alt="%s"', htmlspecialchars($this->options->alt, \ENT_QUOTES)),
            \sprintf('loading="%s"', $this->loadingAttribute()),
            \sprintf('fetchpriority="%s"', $this->fetchPriorityAttribute()),
            \sprintf('decoding="%s"', htmlspecialchars($this->options->decoding, \ENT_QUOTES)),
        ];

        if ($this->fallbackSrcset) {
            $attributes[] = \sprintf('srcset="%s"', htmlspecialchars($this->fallbackSrcset, \ENT_QUOTES));
        }

        if (null !== $this->options->sizes && '' !== $this->options->sizes) {
            $attributes[] = \sprintf('sizes="%s"', htmlspecialchars($this->options->sizes, \ENT_QUOTES));
        }

        if ($this->width) {
            $attributes[] = \sprintf('width="%d"', $this->width);
        }

        if ($this->height) {
            $attributes[] = \sprintf('height="%d"', $this->height);
        }

        if ('' !== $this->options->class) {
            $attributes[] = \sprintf('class="%s"', htmlspecialchars($this->options->class, \ENT_QUOTES));
        }
        $attributes = [...$attributes, ...$this->customAttributes()];

        return \sprintf('<img %s />', implode(' ', $attributes));
    }

    private function loadingAttribute(): string
    {
        return match ($this->options->lazy) {
            true => 'lazy',
            false => 'eager',
        };
    }

    private function fetchPriorityAttribute(): string
    {
        return match ($this->options->fetchPriority) {
            'low' => 'low',
            'high' => 'high',
            default => 'auto',
        };
    }

    /** @return list<string> */
    private function customAttributes(): array
    {
        $attributes = [];
        foreach ($this->options->attributes as $name => $value) {
            if (null === $value || false === $value) {
                continue;
            }
            if (true === $value) {
                $attributes[] = htmlspecialchars($name, \ENT_QUOTES);
                continue;
            }
            $attributes[] = \sprintf('%s="%s"', htmlspecialchars($name, \ENT_QUOTES), htmlspecialchars((string) $value, \ENT_QUOTES));
        }

        return $attributes;
    }
}
