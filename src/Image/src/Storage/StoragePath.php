<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Storage;

/**
 * A canonical, storage-relative path.
 */
final class StoragePath implements \Stringable
{
    public readonly string $value;

    public function __construct(string $value)
    {
        if (str_contains($value, "\0") || str_contains($value, '\\') || str_contains($value, '://')) {
            throw new \Symfony\UX\Image\Exception\InvalidArgumentException('A storage path must be a safe relative path.');
        }

        $value = trim($value, '/');
        if ('' === $value) {
            throw new \Symfony\UX\Image\Exception\InvalidArgumentException('A storage path cannot be empty.');
        }

        foreach (explode('/', $value) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                throw new \Symfony\UX\Image\Exception\InvalidArgumentException('A storage path cannot contain empty, "." or ".." segments.');
            }
        }

        $this->value = $value;
    }

    public static function fromAssetPath(string $path): self
    {
        return new self($path);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
