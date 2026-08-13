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
 * Typed view over the canonical format => list<variant> persistence shape.
 *
 * Art direction is represented by the nullable media field on each variant.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final readonly class ImageSourceSet
{
    /** @var array<string, list<ImageSource>> */
    private array $formats;

    /**
     * @param array<int|string, mixed>|null $raw
     */
    private function __construct(?array $raw)
    {
        if (null === $raw || [] === $raw) {
            $this->formats = [];

            return;
        }
        if (array_is_list($raw)) {
            throw new Exception\InvalidArgumentException('Image variants must use the format => list<variant> shape.');
        }

        $formats = [];
        foreach ($raw as $format => $variants) {
            if (!\is_string($format) || '' === trim($format)) {
                throw new Exception\InvalidArgumentException('Image variant format keys must be non-empty strings.');
            }
            if (!\is_array($variants) || !array_is_list($variants) || [] === $variants) {
                throw new Exception\InvalidArgumentException(\sprintf('Image variants for format "%s" must be a non-empty list.', $format));
            }

            $mapped = [];
            foreach ($variants as $index => $variant) {
                if (!\is_array($variant)) {
                    throw new Exception\InvalidArgumentException(\sprintf('Image variant "%s[%d]" must be an array.', $format, $index));
                }
                /** @var array<string, mixed> $variant */
                $source = ImageSource::fromArray($variant);
                if (null !== $source->format && $source->format !== $format) {
                    throw new Exception\InvalidArgumentException(\sprintf('Image variant "%s[%d]" declares format "%s".', $format, $index, $source->format));
                }
                $mapped[] = $source;
            }
            $formats[$format] = $mapped;
        }

        $this->formats = $formats;
        $this->assertCompatibleDescriptors();
    }

    /**
     * @param array<int|string, mixed>|null $raw
     */
    public static function fromArray(?array $raw): self
    {
        return new self($raw);
    }

    public function isMultiRatio(): bool
    {
        foreach ($this->formats as $variants) {
            foreach ($variants as $variant) {
                if (null !== $variant->media) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isEmpty(): bool
    {
        return [] === $this->formats;
    }

    /**
     * @return list<string>
     */
    public function getAvailableFormats(): array
    {
        return array_keys($this->formats);
    }

    public function getPrimaryForFormat(string $format): ?ImageSource
    {
        return $this->formats[$format][0] ?? null;
    }

    /**
     * @return list<ImageSource>
     */
    public function getForFormat(string $format): array
    {
        return $this->formats[$format] ?? [];
    }

    /**
     * Groups the canonical variants by media query for picture rendering.
     *
     * @return list<array{media: ?string, formats: array<string, list<ImageSource>>}>
     */
    public function getMultiRatioGroups(): array
    {
        /** @var array<string, array{media: ?string, formats: array<string, list<ImageSource>>}> $groups */
        $groups = [];
        foreach ($this->formats as $format => $variants) {
            foreach ($variants as $variant) {
                $key = null === $variant->media ? "\0" : $variant->media;
                $groups[$key] ??= ['media' => $variant->media, 'formats' => []];
                $groups[$key]['formats'][$format][] = $variant;
            }
        }

        $fallback = $groups["\0"] ?? null;
        unset($groups["\0"]);

        $ordered = array_values($groups);
        if (null !== $fallback) {
            $ordered[] = $fallback;
        }

        return $ordered;
    }

    /**
     * @return array<string, list<ImageSource>>
     */
    public function getSingleRatioFormats(): array
    {
        return $this->formats;
    }

    public function buildSrcset(string $format): ?string
    {
        $parts = $this->buildCanonicalEntries($this->getForFormat($format));

        return [] !== $parts ? implode(', ', $parts) : null;
    }

    /**
     * @return array<string, list<array<string, int|string|null>>>
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->formats as $format => $variants) {
            $result[$format] = array_map(
                static fn (ImageSource $variant): array => $variant->toArray(),
                $variants,
            );
        }

        return $result;
    }

    private function assertCompatibleDescriptors(): void
    {
        foreach ($this->formats as $format => $variants) {
            $groups = [];
            foreach ($variants as $variant) {
                $groups[$variant->media ?? "\0"][] = $variant;
            }
            foreach ($groups as $media => $group) {
                if (\count($group) < 2 || $this->hasCommonDescriptorFamily($group)) {
                    continue;
                }

                throw new Exception\InvalidArgumentException(\sprintf('Image variants for format "%s"%s must all expose width descriptors or all expose density descriptors.', $format, "\0" === $media ? '' : \sprintf(' and media "%s"', $media)));
            }
        }
    }

    /**
     * @param list<ImageSource> $variants
     *
     * @return list<string>
     */
    private function buildCanonicalEntries(array $variants): array
    {
        if ([] === $variants) {
            return [];
        }

        $allDensity = true;
        $allWidth = true;
        foreach ($variants as $variant) {
            $allDensity = $allDensity && null !== $variant->density;
            $allWidth = $allWidth && null !== $variant->width;
        }

        $entries = [];
        foreach ($variants as $index => $variant) {
            if ($allDensity) {
                \assert(null !== $variant->density);
                $entries[$variant->density] = \sprintf('%s %s', $variant->path, $variant->density);
            } elseif ($allWidth) {
                \assert(null !== $variant->width);
                $entries[$variant->width.'w'] = \sprintf('%s %dw', $variant->path, $variant->width);
            } elseif (0 === $index) {
                $entries['bare'] = $variant->path;
            }
        }

        return array_values($entries);
    }

    /**
     * @param list<ImageSource> $variants
     */
    private function hasCommonDescriptorFamily(array $variants): bool
    {
        $allDensity = true;
        $allWidth = true;
        foreach ($variants as $variant) {
            $allDensity = $allDensity && null !== $variant->density;
            $allWidth = $allWidth && null !== $variant->width;
        }

        return $allDensity || $allWidth;
    }
}
