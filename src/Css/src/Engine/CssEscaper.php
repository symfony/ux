<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Engine;

use Symfony\UX\Css\Exception\InvalidArgumentException;

/**
 * Port of Panda's `esc()` (packages/shared/src/esc.ts).
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class CssEscaper
{
    public static function escape(string $className): string
    {
        return preg_replace_callback(
            '/([\x{0}-\x{1f}\x{7f}]|^-?[0-9])|^-$|^-|[^\x{80}-\x{10FFFF}A-Za-z0-9_-]/u',
            static function (array $match): string {
                $char = $match[0];
                if ('' === ($match[1] ?? '')) {
                    return '\\'.$char;
                }
                if ("\0" === $char) {
                    return "\u{FFFD}";
                }

                return substr($char, 0, -1).'\\'.dechex(\ord($char[-1]));
            },
            $className,
        ) ?? throw new InvalidArgumentException(\sprintf('The class name "%s" is not valid UTF-8.', $className));
    }
}
