<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Exception;

final class UnknownImageProfileException extends InvalidArgumentException
{
    /**
     * @param list<string> $available
     */
    public static function create(string $profile, array $available): self
    {
        $suffix = $available ? ' Available profiles: '.implode(', ', $available).'.' : ' No profiles are configured.';

        return new self(\sprintf('Unknown image profile "%s".%s', $profile, $suffix));
    }
}
