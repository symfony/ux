<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image;

/**
 * @author Sébastien Jean <sebastien.jean76@gmail.com>
 *
 * @internal
 */
final class ImageRenderer
{
    public function renderImg(
        ?string $src = null,
        ?string $alt = null,
        string|array|null $srcset = null,
        string|array|null $sizes = null,
        ?int $width = null,
        ?int $height = null,
        ?string $loading = null,
        ?string $decoding = null,
        ?string $fetchpriority = null,
        ?string $crossorigin = null,
        ?string $referrerpolicy = null,
        ?bool $ismap = null,
        ?string $usemap = null,
        array $attributes = [],
    ): string {
        if (null === $alt) {
            throw new \InvalidArgumentException('The "alt" attribute must be explicitly provided for the <img> element. Use an empty string for decorative images.');
        }

        if (null === $src && null === $srcset) {
            throw new \InvalidArgumentException('The "src" attribute is required when "srcset" is not provided.');
        }

        $this->validateEnumAttribute('loading', $loading, ['lazy', 'eager']);
        $this->validateEnumAttribute('decoding', $decoding, ['sync', 'async', 'auto']);
        $this->validateEnumAttribute('fetchpriority', $fetchpriority, ['high', 'low', 'auto']);
        $this->validateEnumAttribute('crossorigin', $crossorigin, ['anonymous', 'use-credentials']);
        $this->validateEnumAttribute('referrerpolicy', $referrerpolicy, [
            'no-referrer',
            'no-referrer-when-downgrade',
            'origin',
            'origin-when-cross-origin',
            'same-origin',
            'strict-origin',
            'strict-origin-when-cross-origin',
            'unsafe-url',
        ]);

        if (null !== $srcset) {
            $srcset = $this->normalizeSrcset($srcset);
            $this->validateSrcset($srcset);
        }

        if (null !== $sizes) {
            $sizes = $this->normalizeSizes($sizes);

            if ($this->sizesStartsWithAuto($sizes) && 'lazy' !== $loading) {
                throw new \InvalidArgumentException('The "sizes" attribute set to "auto" requires the "loading" attribute to be set to "lazy".');
            }

            if (null !== $srcset && !$this->srcsetUsesWidthDescriptors($srcset)) {
                throw new \InvalidArgumentException('The "sizes" attribute requires all image candidates in "srcset" to use width descriptors ("w").');
            }
        }

        $attrs = [];

        if (null !== $src) {
            $attrs['src'] = $src;
        }

        $attrs['alt'] = $alt;

        if (null !== $srcset) {
            $attrs['srcset'] = $srcset;
        }

        if (null !== $sizes) {
            $attrs['sizes'] = $sizes;
        }

        if (null !== $width) {
            $attrs['width'] = (string) $width;
        }

        if (null !== $height) {
            $attrs['height'] = (string) $height;
        }

        if (null !== $loading) {
            $attrs['loading'] = $loading;
        }

        if (null !== $decoding) {
            $attrs['decoding'] = $decoding;
        }

        if (null !== $fetchpriority) {
            $attrs['fetchpriority'] = $fetchpriority;
        }

        if (null !== $crossorigin) {
            $attrs['crossorigin'] = $crossorigin;
        }

        if (null !== $referrerpolicy) {
            $attrs['referrerpolicy'] = $referrerpolicy;
        }

        if (true === $ismap) {
            $attrs['ismap'] = true;
        }

        if (null !== $usemap) {
            $attrs['usemap'] = $usemap;
        }

        $attrs = array_merge($attrs, $attributes);

        return '<img'.$this->renderAttributes($attrs).' />';
    }

    public function renderSource(
        string|array $srcset,
        string|array|null $sizes = null,
        ?string $media = null,
        ?string $type = null,
        ?int $width = null,
        ?int $height = null,
        array $attributes = [],
    ): string {
        if (isset($attributes['src'])) {
            throw new \InvalidArgumentException('The "src" attribute is not allowed on a <source> element.');
        }

        $attrs = [];
        $attrs['srcset'] = $this->normalizeSrcset($srcset);
        $this->validateSrcset($attrs['srcset']);

        if (null !== $sizes) {
            $attrs['sizes'] = $this->normalizeSizes($sizes);
        }

        if (null !== $media) {
            $attrs['media'] = $media;
        }

        if (null !== $type) {
            $attrs['type'] = $type;
        }

        if (null !== $width) {
            $attrs['width'] = (string) $width;
        }

        if (null !== $height) {
            $attrs['height'] = (string) $height;
        }

        $attrs = array_merge($attrs, $attributes);

        return '<source'.$this->renderAttributes($attrs).' />';
    }

    public function renderPicture(
        array $sources,
        ?string $src = null,
        ?string $alt = null,
        string|array|null $srcset = null,
        string|array|null $sizes = null,
        ?int $width = null,
        ?int $height = null,
        ?string $loading = null,
        ?string $decoding = null,
        array $attributes = [],
        array $imgAttributes = [],
    ): string {
        $html = '<picture'.$this->renderAttributes($attributes).'>';

        foreach ($sources as $source) {
            $html .= $this->renderSource(
                $source['srcset'],
                $source['sizes'] ?? null,
                $source['media'] ?? null,
                $source['type'] ?? null,
                $source['width'] ?? null,
                $source['height'] ?? null,
                $source['attributes'] ?? [],
            );
        }

        $html .= $this->renderImg(
            $src,
            $alt,
            $srcset,
            $sizes,
            $width,
            $height,
            $loading,
            $decoding,
            fetchpriority: null,
            crossorigin: null,
            referrerpolicy: null,
            ismap: null,
            usemap: null,
            attributes: $imgAttributes,
        );

        $html .= '</picture>';

        return $html;
    }

    private function normalizeSrcset(string|array $srcset): string
    {
        if (\is_string($srcset)) {
            return $srcset;
        }

        return implode(', ', $srcset);
    }

    private function normalizeSizes(string|array $sizes): string
    {
        if (\is_string($sizes)) {
            return $sizes;
        }

        return implode(', ', $sizes);
    }

    private function renderAttributes(array $attributes): string
    {
        $html = '';

        foreach ($attributes as $name => $value) {
            if (true === $value) {
                $html .= ' '.$name;
                continue;
            }

            if (false === $value || null === $value) {
                continue;
            }

            $html .= ' '.$name.'="'.$this->escape((string) $value).'"';
        }

        return $html;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
    }

    private function validateSrcset(string $srcset): void
    {
        $candidates = array_map('trim', explode(',', $srcset));
        $hasWidthDescriptor = false;
        $hasDensityDescriptor = false;
        $seenDescriptors = [];

        foreach ($candidates as $candidate) {
            if ('' === $candidate) {
                continue;
            }

            $parts = preg_split('/\s+/', $candidate);
            $descriptor = $parts[1] ?? '1x';

            if (str_ends_with($descriptor, 'w')) {
                $hasWidthDescriptor = true;
            } else {
                $hasDensityDescriptor = true;
            }

            if (\in_array($descriptor, $seenDescriptors, true)) {
                throw new \InvalidArgumentException(\sprintf('The "srcset" attribute must not contain duplicate descriptors. The descriptor "%s" is specified more than once.', $descriptor));
            }

            $seenDescriptors[] = $descriptor;
        }

        if ($hasWidthDescriptor && $hasDensityDescriptor) {
            throw new \InvalidArgumentException('The "srcset" attribute must not mix width descriptors ("w") and pixel density descriptors ("x").');
        }
    }

    private function sizesStartsWithAuto(string $sizes): bool
    {
        return 'auto' === $sizes || str_starts_with(strtolower($sizes), 'auto,');
    }

    private function srcsetUsesWidthDescriptors(string $srcset): bool
    {
        foreach (array_map('trim', explode(',', $srcset)) as $candidate) {
            if ('' === $candidate) {
                continue;
            }

            $parts = preg_split('/\s+/', $candidate);

            if (!isset($parts[1]) || !str_ends_with($parts[1], 'w')) {
                return false;
            }
        }

        return true;
    }

    private function validateEnumAttribute(string $name, ?string $value, array $allowed): void
    {
        if (null === $value) {
            return;
        }

        if (!\in_array($value, $allowed, true)) {
            throw new \InvalidArgumentException(\sprintf('The "%s" attribute value "%s" is not valid. Allowed values are: "%s".', $name, $value, implode('", "', $allowed)));
        }
    }
}
