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
                $result[] = JsValue::trim($current);
                $current = '';
            } else {
                $current .= $char;
            }
        }
        if ('' !== $current) {
            $result[] = JsValue::trim($current);
        }

        return $result;
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
}
