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

use Symfony\UX\Image\Fit;
use Symfony\UX\Image\ImageTransformation;
use Symfony\UX\Image\Provider\PathEncoder;
use Symfony\UX\Image\Provider\ProviderInterface;

/**
 * @author Romain Monteil <monteil.romain@gmail.com>
 */
final class GlideProvider implements ProviderInterface
{
    /**
     * @param string|null $host       the host serving Glide, or null to generate URLs relative to the application
     * @param string      $pathPrefix the path Glide is served under, like "/img"
     */
    public function __construct(
        private readonly ?string $host,
        private readonly string $pathPrefix = '',

        #[\SensitiveParameter]
        private readonly ?string $signKey = null,
    ) {
    }

    public function getName(): string
    {
        return 'glide';
    }

    public function generateUrl(ImageTransformation $transformation): string
    {
        $parameters = array_filter([
            'w' => $transformation->width,
            'h' => $transformation->height,
            // "crop" is the cover fit in every Glide version, "cover" only exists since Glide 3
            'fit' => match ($transformation->fit) {
                Fit::Cover => 'crop',
                Fit::Contain => 'contain',
                null => null,
            },
            'fm' => 'jpeg' === $transformation->format ? 'jpg' : $transformation->format,
            'q' => $transformation->quality,
        ], static fn (mixed $v): bool => null !== $v);

        $parameters += $transformation->operations;

        $path = $this->pathPrefix.'/'.PathEncoder::encode($transformation->path);

        if (null !== $this->signKey) {
            $parameters['s'] = $this->sign($path, $parameters);
        }

        $query = http_build_query($parameters);

        return \sprintf('%s%s%s', null === $this->host ? '' : 'https://'.$this->host, $path, '' === $query ? '' : '?'.$query);
    }

    public function getSupportedOperations(): array
    {
        return ['or', 'flip', 'crop', 'bri', 'con', 'gam', 'sharp', 'blur', 'pixel', 'filt', 'bg', 'border', 'dpr', 'mark', 'markw', 'markh', 'markx', 'marky', 'markpad', 'markfit', 'markpos', 'markalpha'];
    }

    public function getSupportedFormats(): array
    {
        return ['avif', 'webp', 'jpeg', 'png', 'gif'];
    }

    public function supportsAutoFormat(): bool
    {
        return false;
    }

    /**
     * Signs the URL the way Glide's own signature checks it.
     *
     * The hash covers the path as it appears in the URL, which is what Symfony's Request::getPathInfo() returns on the server.
     *
     * @param array<string, scalar> $parameters
     *
     * @see https://glide.thephpleague.com/3.0/config/security/
     */
    private function sign(string $path, array $parameters): string
    {
        ksort($parameters);

        return md5($this->signKey.':'.ltrim($path, '/').'?'.http_build_query($parameters));
    }
}
