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

/**
 * Cloudinary URL builder implementation.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class CloudinaryUrlBuilder implements CdnUrlBuilderInterface
{
    /**
     * @param array<string, mixed> $profileConfig
     * @param array<string, mixed> $variantConfig
     */
    public function buildUrl(string $baseUrl, string $path, array $profileConfig, array $variantConfig): string
    {
        $this->validateBaseUrl($baseUrl);
        $transformations = [];

        // Example: Convert 'resize' with 'fit' mode to Cloudinary's 'c_fit,w_800,h_600'
        if (isset($variantConfig['width']) && \is_int($variantConfig['width']) && $variantConfig['width'] > 0) {
            $transformations[] = 'w_'.$variantConfig['width'];
        }

        if (isset($variantConfig['height']) && \is_int($variantConfig['height']) && $variantConfig['height'] > 0) {
            $transformations[] = 'h_'.$variantConfig['height'];
        }

        if (isset($variantConfig['mode']) && \is_string($variantConfig['mode'])) {
            $mode = match ($variantConfig['mode']) {
                'crop' => 'c_crop',
                'fit' => 'c_fit',
                'fill' => 'c_fill',
                default => throw new \Symfony\UX\Image\Exception\InvalidArgumentException(\sprintf('Unsupported Cloudinary resize mode "%s".', $variantConfig['mode'])),
            };
            $transformations[] = $mode;
        }

        $format = $variantConfig['format'] ?? null;
        if (\is_string($format) && '' !== $format) {
            if (1 !== preg_match('/^[a-z0-9]+$/i', $format)) {
                throw new \Symfony\UX\Image\Exception\InvalidArgumentException(\sprintf('Unsupported Cloudinary image format "%s".', $format));
            }
            // A <source type="..."> must not resolve to another format through content negotiation.
            $transformations[] = 'f_'.strtolower($format);
        } else {
            $transformations[] = 'f_auto';
        }
        $transformations[] = 'q_auto';

        $transformationString = implode(',', $transformations);

        return rtrim($baseUrl, '/').'/'.$transformationString.'/'.$this->encodePath($path);
    }

    public static function getProviderName(): string
    {
        return 'cloudinary';
    }

    private function validateBaseUrl(string $baseUrl): void
    {
        if (!\in_array(parse_url($baseUrl, \PHP_URL_SCHEME), ['http', 'https'], true) || null !== parse_url($baseUrl, \PHP_URL_USER)) {
            throw new \Symfony\UX\Image\Exception\InvalidArgumentException('A CDN base URL must be an HTTP(S) URL without credentials.');
        }
    }

    private function encodePath(string $path): string
    {
        return implode('/', array_map('rawurlencode', explode('/', trim($path, '/'))));
    }
}
