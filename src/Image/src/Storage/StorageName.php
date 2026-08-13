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
 * A safe storage identifier. Storage names are configuration keys, never paths.
 */
final readonly class StorageName implements \Stringable
{
    public string $value;

    public function __construct(string $value)
    {
        if (1 !== preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/D', $value)) {
            throw new \Symfony\UX\Image\Exception\InvalidArgumentException(\sprintf('Invalid image storage name "%s".', $value));
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
