<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Ipx;

use Symfony\UX\Image\Fit;
use Symfony\UX\Image\ImageTransformation;
use Symfony\UX\Image\Provider\PathEncoder;
use Symfony\UX\Image\Provider\ProviderInterface;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class IpxProvider implements ProviderInterface
{
    private readonly string $baseUrl;

    public function __construct(string $host, ?int $port = null, string $basePath = '', bool $secure = true)
    {
        $scheme = $secure ? 'https' : 'http';
        $authority = null !== $port ? $host.':'.$port : $host;

        $this->baseUrl = $scheme.'://'.$authority.rtrim($basePath, '/');
    }

    public function getName(): string
    {
        return 'ipx';
    }

    public function generateUrl(ImageTransformation $transformation): string
    {
        $width = $transformation->width;
        $height = $transformation->height;

        // IPX applies "w" and "h" as two separate resizes, and reads "fit" only with "s"
        $size = null !== $width && null !== $height ? ['s' => $width.'x'.$height] : ['w' => $width, 'h' => $height];

        $modifiers = array_filter([
            ...$size,
            'fit' => match ($transformation->fit) {
                Fit::Cover => 'cover',
                // sharp's "contain" pads the image to the requested box, "inside" does not
                Fit::Contain => 'inside',
                null => null,
            },
            'f' => $transformation->format,
            'q' => $transformation->quality,
        ], static fn (mixed $v): bool => null !== $v);

        $modifiers += $transformation->operations;

        $parts = [];
        foreach ($modifiers as $name => $value) {
            $name = rawurlencode($name);

            $parts[] = match ($value) {
                true => $name,
                false => $name.'_false',
                // rawurlencode() keeps the "_" that separates arguments, and encodes the "," that separates modifiers
                default => $name.'_'.rawurlencode((string) $value),
            };
        }

        $segment = [] === $parts ? '_' : implode(',', $parts);

        return $this->baseUrl.'/'.$segment.'/'.PathEncoder::encode($transformation->path);
    }

    public function getSupportedOperations(): array
    {
        return ['animated', 'autoorient', 'background', 'blur', 'brightness', 'clahe', 'dilate', 'enlarge', 'erode', 'extend', 'extract', 'flatten', 'flip', 'flop', 'gamma', 'grayscale', 'hue', 'kernel', 'lightness', 'linear', 'median', 'modulate', 'negate', 'normalize', 'opacity', 'position', 'rotate', 'saturation', 'sharpen', 'threshold', 'tint', 'trim', 'unflatten'];
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
