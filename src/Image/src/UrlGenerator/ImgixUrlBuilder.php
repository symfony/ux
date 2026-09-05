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
 * Imgix URL builder implementation.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class ImgixUrlBuilder implements CdnUrlBuilderInterface
{
    /**
     * @param array<string, mixed> $profileConfig
     * @param array<string, mixed> $variantConfig
     */
    public function buildUrl(string $baseUrl, string $path, array $profileConfig, array $variantConfig): string
    {
        if (!\in_array(parse_url($baseUrl, \PHP_URL_SCHEME), ['http', 'https'], true) || null !== parse_url($baseUrl, \PHP_URL_USER)) {
            throw new \Symfony\UX\Image\Exception\InvalidArgumentException('A CDN base URL must be an HTTP(S) URL without credentials.');
        }
        $params = [];

        // Convert dimensions
        if (isset($variantConfig['width']) && \is_int($variantConfig['width']) && $variantConfig['width'] > 0) {
            $params['w'] = $variantConfig['width'];
        }

        if (isset($variantConfig['height']) && \is_int($variantConfig['height']) && $variantConfig['height'] > 0) {
            $params['h'] = $variantConfig['height'];
        }

        // Convert resize modes
        if (isset($variantConfig['mode']) && \is_string($variantConfig['mode'])) {
            $params['fit'] = match ($variantConfig['mode']) {
                'crop' => 'crop',
                'fit' => 'scale',
                'fill' => 'fillmax',
                default => throw new \Symfony\UX\Image\Exception\InvalidArgumentException(\sprintf('Unsupported Imgix resize mode "%s".', $variantConfig['mode'])),
            };
        }

        // Quality setting
        if (isset($variantConfig['quality']) && \is_int($variantConfig['quality']) && $variantConfig['quality'] >= 1 && $variantConfig['quality'] <= 100) {
            $params['q'] = $variantConfig['quality'];
        }

        // Auto-optimize settings
        $params['auto'] = 'format,compress';

        $queryString = http_build_query($params);
        $separator = parse_url($baseUrl, \PHP_URL_QUERY) ? '&' : '?';

        $encodedPath = implode('/', array_map('rawurlencode', explode('/', trim($path, '/'))));

        return rtrim($baseUrl, '/').'/'.$encodedPath.$separator.$queryString;
    }

    public static function getProviderName(): string
    {
        return 'imgix';
    }
}
