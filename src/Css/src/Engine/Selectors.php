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

/**
 * Port of `parseSelectors()` and `getResolvedSelectors()` (packages/core/src/stringify.ts).
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class Selectors
{
    /**
     * @return list<string>
     */
    public static function parse(string $selector): array
    {
        return array_map(JsValue::trim(...), self::split($selector));
    }

    /**
     * Port of `extractTrailingPseudos()` (packages/core/src/selector.ts): the pseudo-elements every selector of the list ends with.
     *
     * @return array{string, string}|array{null, string} the trailing pseudo-elements and the list without them, or null and the list as it was
     */
    public static function extractTrailingPseudos(string $selector): array
    {
        $selectors = self::split($selector);
        $matrix = [];
        foreach ($selectors as $index => $part) {
            $rest = rtrim($part);
            for ($depth = 0; null !== ($pseudo = self::trailingPseudo($rest)); ++$depth) {
                $matrix[$depth][$index] = $pseudo;
                $rest = substr($rest, 0, -\strlen($pseudo));
            }
        }

        $removed = array_fill(0, \count($selectors), 0);
        $trailing = '';
        foreach ($matrix as $pseudos) {
            if (\count($pseudos) !== \count($selectors) || \count(array_unique($pseudos)) > 1) {
                break;
            }

            foreach ($pseudos as $index => $pseudo) {
                ++$removed[$index];
            }
            $trailing = $pseudos[0].$trailing;
        }

        if ('' === $trailing) {
            return [null, $selector];
        }

        $rebuilt = [];
        foreach ($selectors as $index => $part) {
            $rest = rtrim($part);
            $spaces = substr($part, \strlen($rest));
            for ($count = 0; $count < $removed[$index]; ++$count) {
                $rest = substr($rest, 0, \strlen($rest) - \strlen((string) self::trailingPseudo($rest)));
            }
            $rebuilt[] = $rest.$spaces;
        }

        return [$trailing, implode(',', $rebuilt)];
    }

    /**
     * @param list<string> $parents
     * @param list<string> $nested
     *
     * @return list<string>
     */
    public static function resolve(array $parents, array $nested): array
    {
        $resolved = [];
        foreach ($parents as $parent) {
            foreach ($nested as $selector) {
                if (!str_contains($selector, '&')) {
                    $resolved[] = $parent.' '.$selector;
                    continue;
                }

                $replacement = $parent;
                if (preg_match('/[ +>|~]/', $parent) && preg_match('/&.*&/', $selector)) {
                    $replacement = ':is('.$parent.')';
                }
                $resolved[] = str_replace('&', $replacement, $selector);
            }
        }

        return $resolved;
    }

    /**
     * @return list<string>
     */
    private static function split(string $selector): array
    {
        $result = [];
        $depth = 0;
        $current = '';
        $escaped = false;
        foreach (preg_split('//u', $selector, -1, \PREG_SPLIT_NO_EMPTY) as $char) {
            if ('\\' === $char && !$escaped) {
                $escaped = true;
                $current .= $char;
                continue;
            }
            if ($escaped) {
                $escaped = false;
                $current .= $char;
                continue;
            }
            if ('(' === $char) {
                ++$depth;
            } elseif (')' === $char) {
                --$depth;
            }
            if (',' === $char && 0 === $depth) {
                $result[] = $current;
                $current = '';
            } else {
                $current .= $char;
            }
        }
        if ('' !== $current) {
            $result[] = $current;
        }

        return $result;
    }

    private static function trailingPseudo(string $selector): ?string
    {
        $end = \strlen($selector);
        if (str_ends_with($selector, ')')) {
            $depth = 0;
            for ($start = $end - 1; $start >= 0; --$start) {
                $depth += match ($selector[$start]) {
                    ')' => 1,
                    '(' => -1,
                    default => 0,
                };
                if (0 === $depth) {
                    break;
                }
            }
            if ($start < 0) {
                return null;
            }
            $end = $start;
        }

        if (!preg_match('/::[-\w]+$/', substr($selector, 0, $end), $matches)) {
            return null;
        }

        return substr($selector, $end - \strlen($matches[0]));
    }
}
