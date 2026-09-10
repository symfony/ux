<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Cloudflare;

use Symfony\UX\Image\Fit;
use Symfony\UX\Image\ImageTransformation;
use Symfony\UX\Image\Provider\PathEncoder;
use Symfony\UX\Image\Provider\ProviderInterface;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class CloudflareProvider implements ProviderInterface
{
    public function __construct(
        private readonly string $host,
    ) {
    }

    public function getName(): string
    {
        return 'cloudflare';
    }

    public function generateUrl(ImageTransformation $transformation): string
    {
        $options = array_filter([
            'width' => $transformation->width,
            'height' => $transformation->height,
            'fit' => match ($transformation->fit) {
                Fit::Cover => 'cover',
                Fit::Contain => 'contain',
                Fit::ScaleDown => 'scale-down',
                null => null,
            },
            'format' => $transformation->format,
            'quality' => $transformation->quality,
        ], static fn (mixed $v): bool => null !== $v);

        $options += $transformation->operations;

        $path = PathEncoder::encode($transformation->path);

        // An empty "/cdn-cgi/image//" segment isn't a URL Cloudflare serves, so skip it entirely and return the origin URL.
        if ([] === $options) {
            return \sprintf('https://%s/%s', $this->host, $path);
        }

        // PHP_QUERY_RFC3986 encodes each key/value: a raw "#", "/", "?" or space would otherwise end or escape this comma-joined segment.
        // Values are cast to string first so a boolean false serializes as "" like (string) does, not as http_build_query's own "0".
        $encodedOptions = http_build_query(array_map(static fn (mixed $v): string => (string) $v, $options), '', ',', \PHP_QUERY_RFC3986);

        return \sprintf('https://%s/cdn-cgi/image/%s/%s', $this->host, $encodedOptions, $path);
    }

    public function getSupportedOperations(): array
    {
        return ['gravity', 'dpr', 'rotate', 'trim', 'blur', 'brightness', 'contrast', 'gamma', 'saturation', 'sharpen', 'background', 'border', 'anim', 'metadata', 'onerror', 'compression'];
    }

    public function getSupportedFormats(): array
    {
        return ['avif', 'webp', 'jpeg', 'png'];
    }

    public function supportsAutoFormat(): bool
    {
        return true;
    }
}
