<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Imgix;

use Symfony\UX\Image\Fit;
use Symfony\UX\Image\ImageTransformation;
use Symfony\UX\Image\Provider\PathEncoder;
use Symfony\UX\Image\Provider\ProviderInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ImgixProvider implements ProviderInterface
{
    public function __construct(
        private readonly string $host,

        #[\SensitiveParameter]
        private readonly ?string $signKey = null,
    ) {
    }

    public function getName(): string
    {
        return 'imgix';
    }

    public function generateUrl(ImageTransformation $transformation): string
    {
        $parameters = array_filter([
            'w' => $transformation->width,
            'h' => $transformation->height,
            'fit' => match ($transformation->fit) {
                Fit::Cover => 'crop',
                Fit::Contain => 'clip',
                null => null,
            },
            'fm' => match ($transformation->format) {
                'auto' => null,
                'jpeg' => 'jpg',
                default => $transformation->format,
            },
            'q' => $transformation->quality,
            // imgix negotiates the format through "auto", which also takes the "compress" and "enhance" flags
            'auto' => implode(',', array_filter(['auto' === $transformation->format ? 'format' : null, $transformation->operations['auto'] ?? null])),
        ], static fn (mixed $v): bool => null !== $v && '' !== $v);

        $parameters += $transformation->operations;

        $path = '/'.PathEncoder::encode($transformation->path);
        $query = [] === $parameters ? '' : '?'.http_build_query($parameters, '', '&', \PHP_QUERY_RFC3986);

        return \sprintf('https://%s%s', $this->host, $this->sign($path.$query));
    }

    public function getSupportedOperations(): array
    {
        return ['auto', 'bg', 'blur', 'border', 'bri', 'con', 'crop', 'dpr', 'exp', 'fill', 'fill-color', 'flip', 'fp-x', 'fp-y', 'fp-z', 'gam', 'high', 'invert', 'monochrome', 'orient', 'pad', 'rect', 'rot', 'sat', 'sepia', 'shad', 'sharp', 'trim', 'usm', 'vib'];
    }

    public function getSupportedFormats(): array
    {
        return ['avif', 'webp', 'jpeg', 'png'];
    }

    public function supportsAutoFormat(): bool
    {
        return true;
    }

    /**
     * @see https://docs.imgix.com/en-US/getting-started/setup/securing-assets
     */
    private function sign(string $pathAndQuery): string
    {
        if (null === $this->signKey) {
            return $pathAndQuery;
        }

        $separator = str_contains($pathAndQuery, '?') ? '&' : '?';

        return $pathAndQuery.$separator.'s='.md5($this->signKey.$pathAndQuery);
    }
}
