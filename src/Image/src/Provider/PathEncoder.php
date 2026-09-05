<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Provider;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class PathEncoder
{
    /**
     * Encodes a path for safe URL transmission.
     *
     * Each segment is rawurlencode'd individually, which keeps a "?", "#" or space inside a
     * filename from being read as a query string, a fragment or a segment break. It does NOT
     * neutralize dot-segments -- rawurlencode('..') is '..' -- so this is only half of the
     * path-safety invariant: {@see \Symfony\UX\Image\ImageTransformation} rejects "." and ".."
     * segments up front, and every provider must keep relying on it.
     */
    public static function encode(string $path): string
    {
        $segments = explode('/', ltrim($path, '/'));

        return implode('/', array_map(rawurlencode(...), $segments));
    }
}
