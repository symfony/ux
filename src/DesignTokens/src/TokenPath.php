<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens;

use Symfony\UX\DesignTokens\Exception\InvalidArgumentException;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class TokenPath
{
    public static function toCssVariable(string $path, ?string $prefix = null): string
    {
        $name = preg_replace('/[^\p{L}\p{N}_-]+/u', '-', str_replace('.', '-', $path));
        $name = trim($name ?? '', '-');

        if (null !== $prefix) {
            self::validateCssPrefix($prefix);
            $name = $prefix.'-'.('' !== $name ? $name : 'token');
        }

        return '--'.('' !== $name ? $name : 'token');
    }

    public static function toCssReference(string $path, ?string $fallback = null, ?string $prefix = null): string
    {
        $variable = self::toCssVariable($path, $prefix);

        return null === $fallback ? "var({$variable})" : "var({$variable}, {$fallback})";
    }

    /** @internal */
    public static function validateCssPrefix(string $prefix): void
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_-]*$/', $prefix)) {
            throw new InvalidArgumentException(\sprintf('CSS prefix "%s" must start with a letter or underscore and contain only letters, digits, underscores, or hyphens.', $prefix));
        }
    }
}
