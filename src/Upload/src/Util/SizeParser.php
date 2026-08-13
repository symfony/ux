<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Util;

use Symfony\UX\Upload\Exception\InvalidArgumentException;

/**
 * Utility class for parsing human-readable size strings.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class SizeParser
{
    /**
     * Parse a human-readable size string (e.g., "5M", "10G") into bytes.
     *
     * @param string|int $size The size to parse (e.g., "5M", "100K", "1G", or an integer)
     *
     * @return int The size in bytes
     *
     * @throws \InvalidArgumentException If the size format is invalid
     */
    public static function parse(string|int $size): int
    {
        if (\is_int($size)) {
            return $size;
        }

        if (!preg_match('/^(\d+)([KMGT]?)$/i', $size, $matches)) {
            throw new InvalidArgumentException(\sprintf('Invalid size "%s".', $size));
        }

        $value = (int) $matches[1];
        $unit = strtoupper($matches[2]);

        return match ($unit) {
            'K' => $value * 1024,
            'M' => $value * 1024 * 1024,
            'G' => $value * 1024 * 1024 * 1024,
            'T' => $value * 1024 * 1024 * 1024 * 1024,
            default => $value,
        };
    }
}
