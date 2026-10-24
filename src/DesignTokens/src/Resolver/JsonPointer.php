<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Resolver;

use Symfony\UX\DesignTokens\Exception\InvalidArgumentException;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class JsonPointer
{
    /**
     * @param string $fragment the part after "#": empty for the whole document, or starting with "/"
     *
     * @return list<string>
     *
     * @throws InvalidArgumentException when the fragment is not a JSON Pointer
     */
    public static function segments(string $fragment): array
    {
        if (1 === preg_match('/%(?![0-9a-f]{2})/i', $fragment)) {
            throw new InvalidArgumentException(\sprintf('Invalid percent-encoding in JSON Pointer fragment: "%s".', $fragment));
        }
        $pointer = rawurldecode($fragment);
        if ('' === $pointer) {
            return [];
        }
        if (!str_starts_with($pointer, '/')) {
            throw new InvalidArgumentException(\sprintf('Invalid JSON Pointer fragment: "%s".', $fragment));
        }

        return array_map(static function (string $segment): string {
            if (1 === preg_match('/~(?![01])/', $segment)) {
                throw new InvalidArgumentException(\sprintf('Invalid JSON Pointer escape in segment "%s".', $segment));
            }

            return str_replace(['~1', '~0'], ['/', '~'], $segment);
        }, explode('/', substr($pointer, 1)));
    }

    public static function escape(string $segment): string
    {
        return str_replace(['~', '/'], ['~0', '~1'], $segment);
    }
}
