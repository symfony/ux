<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\KeyCdn;

use Symfony\UX\Image\Fit;
use Symfony\UX\Image\ImageTransformation;
use Symfony\UX\Image\Provider\PathEncoder;
use Symfony\UX\Image\Provider\ProviderInterface;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class KeyCdnProvider implements ProviderInterface
{
    public function __construct(
        private readonly string $host,
    ) {
    }

    public function getName(): string
    {
        return 'keycdn';
    }

    public function generateUrl(ImageTransformation $transformation): string
    {
        $options = array_filter([
            'width' => $transformation->width,
            'height' => $transformation->height,
            'fit' => match ($transformation->fit) {
                Fit::Cover => 'cover',
                Fit::Contain => 'contain',
                Fit::ScaleDown => 'inside',
                null => null,
            },
            'format' => $transformation->format,
            'quality' => $transformation->quality,
        ], static fn (mixed $v): bool => null !== $v);

        $options += $transformation->operations;

        $path = PathEncoder::encode($transformation->path);

        return \sprintf('https://%s/%s?%s', $this->host, $path, http_build_query($options));
    }

    public function getSupportedOperations(): array
    {
        return ['position', 'enlarge', 'trim', 'crop', 'bg', 'rotate', 'flip', 'flop', 'sharpen', 'blur', 'gamma', 'grayscale', 'progressive', 'lossless', 'metadata'];
    }

    public function getSupportedFormats(): array
    {
        return ['webp', 'jpeg', 'png'];
    }

    public function supportsAutoFormat(): bool
    {
        return false;
    }
}
