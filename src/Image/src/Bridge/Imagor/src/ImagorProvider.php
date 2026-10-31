<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Imagor;

use Symfony\UX\Image\Exception\InvalidArgumentException;
use Symfony\UX\Image\Fit;
use Symfony\UX\Image\ImageTransformation;
use Symfony\UX\Image\Provider\PathEncoder;
use Symfony\UX\Image\Provider\ProviderInterface;

/**
 * @author Symfony Community <https://symfony.com/contributors>
 */
final class ImagorProvider implements ProviderInterface
{
    private const FLAGS = ['grayscale', 'no_upscale', 'strip_exif', 'strip_metadata'];

    private readonly string $endpoint;
    private readonly ?string $origin;

    /**
     * @param string      $endpoint     the base URL of the Imagor server, with its optional path prefix
     * @param string|null $origin       the base URL Imagor fetches the originals from; null passes the path to the server's own loader
     * @param string      $signer       the HMAC algorithm of the server's IMAGOR_SIGNER_TYPE: sha1, sha256 or sha512
     * @param int         $signTruncate the server's IMAGOR_SIGNER_TRUNCATE; 0 keeps the whole signature
     */
    public function __construct(
        string $endpoint,

        #[\SensitiveParameter]
        private readonly ?string $signKey = null,
        ?string $origin = null,
        private readonly string $signer = 'sha1',
        private readonly int $signTruncate = 0,
    ) {
        $this->endpoint = rtrim($endpoint, '/');
        $this->origin = null === $origin ? null : rtrim($origin, '/');
    }

    public function getName(): string
    {
        return 'imagor';
    }

    public function generateUrl(ImageTransformation $transformation): string
    {
        $parts = [];
        if (Fit::Contain === $transformation->fit) {
            $parts[] = 'fit-in';
        }
        if (null !== $transformation->width || null !== $transformation->height) {
            $parts[] = ($transformation->width ?? 0).'x'.($transformation->height ?? 0);
        }

        $filters = [];
        if (null !== $transformation->quality) {
            $filters[] = 'quality('.$transformation->quality.')';
        }
        if (null !== $transformation->format) {
            $filters[] = 'format('.$transformation->format.')';
        }
        foreach ($transformation->operations as $name => $value) {
            if (\in_array($name, self::FLAGS, true)) {
                if (!\is_bool($value)) {
                    throw new InvalidArgumentException(\sprintf('The Imagor "%s" operation must be a boolean.', $name));
                }
                if ($value) {
                    $filters[] = $name.'()';
                }

                continue;
            }

            $filters[] = $name.'('.$this->encodeArguments($name, $value).')';
        }
        if ([] !== $filters) {
            $parts[] = 'filters:'.implode(':', $filters);
        }

        // Encode the entire source: a storage key must not become an Imagor option,
        // and a remote URL's query must remain part of the source, not this request.
        $parts[] = rawurlencode($this->resolveSource($transformation->path));
        $path = implode('/', $parts);

        return $this->endpoint.'/'.$this->sign($path).'/'.$path;
    }

    public function getSupportedOperations(): array
    {
        return ['background_color', 'blur', 'brightness', 'contrast', 'crop', 'dpi', 'fill', 'focal', 'grayscale', 'hue', 'no_upscale', 'page', 'rgb', 'rotate', 'round_corner', 'saturation', 'sharpen', 'strip_exif', 'strip_metadata'];
    }

    public function getSupportedFormats(): array
    {
        return ['avif', 'webp', 'jpeg', 'png'];
    }

    public function supportsAutoFormat(): bool
    {
        // Negotiation depends on server flags, not a URL parameter.
        return false;
    }

    private function encodeArguments(string $name, mixed $value): string
    {
        $arguments = \is_string($value) || \is_int($value) || \is_float($value) ? (string) $value : '';

        // Keep filter delimiters out of arguments. Commas and colons are used by crop and focal.
        if (!preg_match('/\A[a-zA-Z0-9.,:_-]+\z/', $arguments)) {
            throw new InvalidArgumentException(\sprintf('The Imagor "%s" operation has invalid arguments.', $name));
        }

        return $arguments;
    }

    private function resolveSource(string $path): string
    {
        if (null === $this->origin) {
            return ltrim($path, '/');
        }

        return $this->origin.'/'.PathEncoder::encode($path);
    }

    /**
     * @see https://docs.imagor.net/security/
     */
    private function sign(string $path): string
    {
        if (null === $this->signKey) {
            return 'unsafe';
        }

        $hmac = hash_hmac($this->signer, $path, $this->signKey, true);
        $signature = strtr(base64_encode($hmac), '+/', '-_');

        return $this->signTruncate > 0 ? substr($signature, 0, $this->signTruncate) : $signature;
    }
}
