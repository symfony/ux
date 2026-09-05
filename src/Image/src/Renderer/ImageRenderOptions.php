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

/**
 * Immutable rendering options describing how an image should be output.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class ImageRenderOptions
{
    /** @var list<string>|null */
    public readonly ?array $srcset;

    /**
     * @param array<string, scalar|null>   $attributes arbitrary attributes for the rendered <img>
     * @param array<array-key, mixed>|null $srcset     explicit fallback srcset entries
     */
    public function __construct(
        public readonly ?string $sizes = null,
        public readonly string $alt = '',
        public readonly bool $lazy = true,
        public readonly string $fetchPriority = 'auto',
        public readonly string $class = '',
        public readonly string $decoding = 'async',
        public readonly ?string $variant = null,
        ?array $srcset = null,
        public readonly array $attributes = [],
    ) {
        if (!\in_array($fetchPriority, ['auto', 'high', 'low'], true)) {
            throw new \Symfony\UX\Image\Exception\InvalidArgumentException(\sprintf('Invalid image fetch priority "%s".', $fetchPriority));
        }
        if (!\in_array($decoding, ['sync', 'async', 'auto'], true)) {
            throw new \Symfony\UX\Image\Exception\InvalidArgumentException(\sprintf('Invalid image decoding hint "%s".', $decoding));
        }
        if (null !== $variant && '' === trim($variant)) {
            throw new \Symfony\UX\Image\Exception\InvalidArgumentException('Image variant must not be empty when provided.');
        }

        $normalizedSrcset = null;
        if (null !== $srcset) {
            $normalizedSrcset = [];
            foreach ($srcset as $entry) {
                if (!\is_string($entry)) {
                    throw new \Symfony\UX\Image\Exception\InvalidArgumentException('Image srcset entries must be strings.');
                }
                if ('' === trim($entry)) {
                    throw new \Symfony\UX\Image\Exception\InvalidArgumentException('Image srcset entries must not be empty.');
                }
                $normalizedSrcset[] = $entry;
            }
        }
        $this->srcset = $normalizedSrcset;

        foreach ($attributes as $name => $value) {
            if (1 !== preg_match('/^[a-zA-Z_:][a-zA-Z0-9:._-]*$/', $name) || str_starts_with(strtolower($name), 'on')) {
                throw new \Symfony\UX\Image\Exception\InvalidArgumentException(\sprintf('Unsafe image attribute name "%s".', $name));
            }
            if (\in_array(strtolower($name), ['src', 'srcset', 'sizes', 'alt', 'width', 'height', 'loading', 'fetchpriority', 'decoding', 'class'], true)) {
                throw new \Symfony\UX\Image\Exception\InvalidArgumentException(\sprintf('Image attribute "%s" is managed by ImageRenderOptions.', $name));
            }
            if (!\is_scalar($value) && null !== $value) {
                throw new \Symfony\UX\Image\Exception\InvalidArgumentException(\sprintf('Image attribute "%s" must be scalar or null.', $name));
            }
        }
    }

    public function getSizes(): ?string
    {
        return $this->sizes;
    }

    public function getAlt(): string
    {
        return $this->alt;
    }

    public function isLazy(): bool
    {
        return $this->lazy;
    }

    public function getFetchPriority(): string
    {
        return $this->fetchPriority;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getDecoding(): string
    {
        return $this->decoding;
    }

    public function getVariant(): ?string
    {
        return $this->variant;
    }

    /**
     * @return list<string>|null
     */
    public function getSrcset(): ?array
    {
        return $this->srcset;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
