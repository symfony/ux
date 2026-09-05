<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Profile;

use Symfony\UX\Image\Exception\InvalidArgumentException;

enum ProcessingMode: string
{
    case Immediate = 'immediate';
    case Deferred = 'deferred';
    case Async = 'async';

    /** @param array<string, mixed>|null $profile */
    public static function fromProfile(?array $profile): self
    {
        if (null === $profile) {
            return self::Deferred;
        }

        $value = $profile['processing'] ?? self::Immediate->value;
        if (!\is_string($value)) {
            throw new InvalidArgumentException(\sprintf('Image processing mode must be a string, "%s" given.', get_debug_type($value)));
        }

        $mode = self::tryFrom($value) ?? throw new InvalidArgumentException(\sprintf('Invalid image processing mode "%s".', $value));

        return $mode;
    }
}
