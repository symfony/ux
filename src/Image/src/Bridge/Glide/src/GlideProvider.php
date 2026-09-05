<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Glide;

use League\Glide\Urls\UrlBuilder;
use League\Glide\Urls\UrlBuilderFactory;
use Symfony\UX\Image\Fit;
use Symfony\UX\Image\ImageTransformation;
use Symfony\UX\Image\Provider\PathEncoder;
use Symfony\UX\Image\Provider\ProviderInterface;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class GlideProvider implements ProviderInterface
{
    public const array SUPPORTED_OPERATIONS = ['crop', 'or', 'bri', 'con', 'gam', 'sharp', 'blur', 'pixel', 'filt', 'bg', 'border'];
    public const array SUPPORTED_FORMATS = ['avif', 'webp', 'jpeg', 'pjpg', 'png', 'gif', 'heic'];

    private readonly UrlBuilder $urlBuilder;

    public function __construct(
        private readonly string $urlPrefix,
        private readonly ?string $signKey = null,
    ) {
        $this->urlBuilder = UrlBuilderFactory::create($this->urlPrefix, $this->signKey);
    }

    public function getName(): string
    {
        return 'glide';
    }

    public function generateUrl(ImageTransformation $transformation): string
    {
        $options = array_filter([
            'w' => $transformation->width,
            'h' => $transformation->height,
            'fit' => match ($transformation->fit) {
                Fit::Cover => 'crop',
                Fit::Contain => 'contain',
                Fit::ScaleDown => 'max',
                null => null,
            },
            'fm' => null !== $transformation->format ? self::toGlideFormat($transformation->format) : null,
            'q' => $transformation->quality,
        ], static fn (mixed $v): bool => null !== $v);

        $options += $transformation->operations;

        $path = PathEncoder::encode($transformation->path);

        return $this->urlBuilder->getUrl($path, $options);
    }

    public function getSupportedOperations(): array
    {
        return self::SUPPORTED_OPERATIONS;
    }

    public function getSupportedFormats(): array
    {
        return self::SUPPORTED_FORMATS;
    }

    public function supportsAutoFormat(): bool
    {
        return true;
    }

    /**
     * Translates the shared "jpeg" spelling (SUPPORTED_FORMATS, getSupportedFormats(), every other
     * provider) to the one Glide's own "fm" parameter accepts -- League\Glide\Api\Encoder::supportedFormats()
     * rejects "jpeg" outright. Every other format name, including "jpg" itself, is already Glide's own spelling.
     */
    public static function toGlideFormat(string $format): string
    {
        return 'jpeg' === $format ? 'jpg' : $format;
    }
}
