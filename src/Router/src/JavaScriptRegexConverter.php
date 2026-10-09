<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Router;

/**
 * Converts a route requirement to a JavaScript regex source, or returns null when JavaScript cannot express it faithfully.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class JavaScriptRegexConverter
{
    // Same pattern as UrlGenerator::doGenerate(), which ignores look-arounds when checking requirements
    private const LOOKAROUND_REGEX = '/\(\?(?:=|<=|!|<!)((?:[^()\\\\]+|\\\\.|\((?1)\))*)\)/';
    private const SUPPORTED_LETTER_ESCAPES = 'bBcdDfnrsStwWx';
    private const UNICODE_WORD = '\p{L}\p{N}\p{Mn}\p{Pc}';

    public static function convert(string $regex, bool $utf8): ?string
    {
        $regex = preg_replace(self::LOOKAROUND_REGEX, '', $regex);
        $length = \strlen($regex);
        $result = '';
        $quantified = false;

        for ($i = 0; $i < $length; ++$i) {
            $char = $regex[$i];

            if ('\\' === $char) {
                if (null === $escape = self::readEscape($regex, $i, $utf8, false)) {
                    return null;
                }
                $result .= $escape[0];
                $i += $escape[1] - 1;
                $quantified = false;
                continue;
            }

            if ('[' === $char) {
                if (null === $class = self::readCharacterClass($regex, $i, $utf8)) {
                    return null;
                }
                $result .= $class[0];
                $i += $class[1] - 1;
                $quantified = false;
                continue;
            }

            if ('(' === $char && '?' === ($regex[$i + 1] ?? null)) {
                if (!preg_match('/^(?::|<[A-Za-z])/', substr($regex, $i + 2, 2))) {
                    return null;
                }
                $result .= '(?';
                ++$i;
                $quantified = false;
                continue;
            }

            if ('+' === $char && $quantified) {
                $quantified = false;
                continue;
            }

            $result .= $char;
            $quantified = str_contains('*+?}', $char);
        }

        return $result;
    }

    /**
     * @return array{string, int}|null The converted escape and its length in the source regex
     */
    private static function readEscape(string $regex, int $offset, bool $utf8, bool $inClass): ?array
    {
        $next = $regex[$offset + 1] ?? null;

        if (null === $next) {
            return null;
        }

        if (!ctype_alpha($next)) {
            return ['\\'.$next, 2];
        }

        if (('p' === $next || 'P' === $next) && $utf8 && '{' === ($regex[$offset + 2] ?? null)) {
            $end = strpos($regex, '}', $offset + 3);

            return false === $end ? null : [substr($regex, $offset, $end - $offset + 1), $end - $offset + 1];
        }

        if ('x' === $next && '{' === ($regex[$offset + 2] ?? null) || !str_contains(self::SUPPORTED_LETTER_ESCAPES, $next)) {
            return null;
        }

        if (!$utf8) {
            return ['\\'.$next, 2];
        }

        // With the "u" modifier, \w and \d match Unicode letters and digits in PCRE, but only ASCII ones in JavaScript
        return match ($next) {
            'd' => ['\p{Nd}', 2],
            'D' => ['\P{Nd}', 2],
            'w' => [$inClass ? self::UNICODE_WORD : '['.self::UNICODE_WORD.']', 2],
            'W' => $inClass ? null : ['[^'.self::UNICODE_WORD.']', 2],
            'b' => $inClass ? ['\b', 2] : null,
            'B' => null,
            default => ['\\'.$next, 2],
        };
    }

    /**
     * @return array{string, int}|null The converted class and its length in the source regex
     */
    private static function readCharacterClass(string $regex, int $offset, bool $utf8): ?array
    {
        $length = \strlen($regex);
        $converted = '[';
        $i = $offset + 1;

        if ('^' === ($regex[$i] ?? null)) {
            $converted .= '^';
            ++$i;
        }

        // PCRE reads a leading "]" as a literal, JavaScript in unicode mode reads it as the end of an empty class
        if (']' === ($regex[$i] ?? null)) {
            $converted .= '\\]';
            ++$i;
        }

        for (; $i < $length; ++$i) {
            $char = $regex[$i];

            if (']' === $char) {
                return [$converted.']', $i - $offset + 1];
            }

            if ('[' === $char && ':' === ($regex[$i + 1] ?? null)) {
                return null;
            }

            if ('\\' === $char) {
                if (null === $escape = self::readEscape($regex, $i, $utf8, true)) {
                    return null;
                }
                $converted .= $escape[0];
                $i += $escape[1] - 1;
                continue;
            }

            $converted .= $char;
        }

        return null;
    }
}
