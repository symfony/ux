<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image;

use Symfony\UX\Image\Exception\InvalidArgumentException;

/**
 * A single image output, normalised across providers.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class ImageTransformation
{
    /**
     * @param array<string, scalar> $operations provider-specific extras
     */
    public function __construct(
        public readonly string $path,
        public readonly ?int $width = null,
        public readonly ?int $height = null,
        public readonly ?Fit $fit = null,
        public readonly ?string $format = null,
        public readonly ?int $quality = null,
        public readonly array $operations = [],
    ) {
        if ('' === trim($path)) {
            throw new InvalidArgumentException('The image path must not be empty.');
        }
        foreach (explode('/', $path) as $segment) {
            if ('.' === $segment || '..' === $segment) {
                throw new InvalidArgumentException(\sprintf('The image path "%s" must not contain a "." or ".." segment.', $path));
            }
        }
        if (null !== $width && $width < 1) {
            throw new InvalidArgumentException(\sprintf('The image width must be a positive integer, %d given.', $width));
        }
        if (null !== $height && $height < 1) {
            throw new InvalidArgumentException(\sprintf('The image height must be a positive integer, %d given.', $height));
        }
        if (null !== $quality && ($quality < 1 || $quality > 100)) {
            throw new InvalidArgumentException(\sprintf('The image quality must be between 1 and 100, %d given.', $quality));
        }
    }
}
