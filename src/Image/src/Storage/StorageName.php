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

use Symfony\UX\Image\Exception\InvalidArgumentException;

/**
 * A safe storage identifier. Storage names are configuration keys, never paths.
 */
final class StorageName implements \Stringable
{
    public readonly string $value;

    public function __construct(string $value)
    {
        self::assertValid($value);

        $this->value = $value;
    }

    public static function assertValid(string $value): void
    {
        if (1 !== preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/D', $value)) {
            throw new InvalidArgumentException(\sprintf('Invalid image storage name "%s".', $value));
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
