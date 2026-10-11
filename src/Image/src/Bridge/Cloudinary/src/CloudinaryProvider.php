<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Cloudinary;

use Symfony\UX\Image\Exception\InvalidArgumentException;
use Symfony\UX\Image\Fit;
use Symfony\UX\Image\ImageTransformation;
use Symfony\UX\Image\Provider\PathEncoder;
use Symfony\UX\Image\Provider\ProviderInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CloudinaryProvider implements ProviderInterface
{
    private readonly ?string $origin;

    /**
     * @param string|null $origin the base URL Cloudinary fetches the originals from; null delivers them from the media library, by public ID
     */
    public function __construct(
        private readonly string $cloudName,
        ?string $origin = null,

        #[\SensitiveParameter]
        private readonly ?string $apiSecret = null,
    ) {
        $this->origin = null === $origin ? null : rtrim($origin, '/');
    }

    public function getName(): string
    {
        return 'cloudinary';
    }

    public function generateUrl(ImageTransformation $transformation): string
    {
        $parameters = array_filter([
            'w' => $transformation->width,
            'h' => $transformation->height,
            'c' => match ($transformation->fit) {
                Fit::Cover => 'fill',
                Fit::Contain => 'fit',
                null => null,
            },
            'f' => 'jpeg' === $transformation->format ? 'jpg' : $transformation->format,
            'q' => $transformation->quality,
        ], static fn (mixed $v): bool => null !== $v);

        $parameters += $transformation->operations;

        $components = [];

        foreach ($parameters as $name => $value) {
            $components[] = $name.'_'.$this->encodeValue($name, (string) $value);
        }

        $transformationString = implode(',', $components);
        $prefix = null === $this->origin ? '' : $this->origin.'/';
        $path = ltrim($transformationString.'/'.$prefix.PathEncoder::encode($transformation->path), '/');

        if (null !== $this->apiSecret) {
            $path = $this->sign($transformationString, $prefix.ltrim($transformation->path, '/')).'/'.$path;
        }

        return \sprintf('https://res.cloudinary.com/%s/image/%s/%s', rawurlencode($this->cloudName), null === $this->origin ? 'upload' : 'fetch', $path);
    }

    public function getSupportedOperations(): array
    {
        return ['a', 'b', 'bo', 'co', 'd', 'dpr', 'e', 'fl', 'g', 'o', 'r', 't', 'x', 'y', 'z'];
    }

    public function getSupportedFormats(): array
    {
        return ['avif', 'webp', 'jpeg', 'png'];
    }

    public function supportsAutoFormat(): bool
    {
        return true;
    }

    private function encodeValue(string $name, string $value): string
    {
        // Cloudinary decodes the URL before parsing the transformation, so an escaped "/" or "," would still split it.
        if (preg_match('#[/,]|%2f|%2c#i', $value)) {
            throw new InvalidArgumentException(\sprintf('The value "%s" of the "%s" Cloudinary operation must not contain a "/" or a ",".', $value, $name));
        }

        return str_replace('%3A', ':', rawurlencode($value));
    }

    /**
     * @see https://cloudinary.com/documentation/control_access_to_media#signed_delivery_urls
     */
    private function sign(string $transformationString, string $source): string
    {
        $toSign = ltrim($transformationString.'/'.$source, '/');
        $signature = strtr(base64_encode(sha1($toSign.$this->apiSecret, true)), '+/', '-_');

        return 's--'.substr($signature, 0, 8).'--';
    }
}
