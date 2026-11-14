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
 * Port of `sortStyleRules()` (packages/core/src/sort-style-rules.ts).
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class SortStyleRules
{
    private const PSEUDO_ORDER = [
        ':link',
        ':visited',
        ':focus-within',
        ':focus',
        ':focus-visible',
        ':hover',
        ':active',
    ];

    /**
     * @param list<AtomicStyle> $styles
     *
     * @return list<AtomicStyle>
     */
    public static function sort(array $styles): array
    {
        $declarations = [];
        $withSelectorsOnly = [];
        $withAtRules = [];
        foreach ($styles as $style) {
            if (!$style->conditions) {
                $declarations[] = $style;
            } elseif (!self::hasAtRule($style->conditions)) {
                $withSelectorsOnly[] = $style;
            } else {
                $withAtRules[] = $style;
            }
        }

        return [
            ...self::sortByProperty($declarations),
            ...self::sortBySelectors($withSelectorsOnly),
            ...self::sortByAtRules($withAtRules),
        ];
    }

    /**
     * @param list<AtomicStyle> $styles
     *
     * @return list<AtomicStyle>
     */
    private static function sortByProperty(array $styles): array
    {
        $priorities = self::priorities($styles);

        return self::reorder($styles, static fn (int $a, int $b): int => $priorities[$a] - $priorities[$b]);
    }

    /**
     * @param list<AtomicStyle> $styles
     *
     * @return list<AtomicStyle>
     */
    private static function sortBySelectors(array $styles): array
    {
        $priorities = self::priorities($styles);
        $depths = [];
        $scores = [];
        foreach ($styles as $index => $style) {
            $depths[$index] = \count($style->conditions);
            $scores[$index] = self::pseudoScore($style->conditions[0]->value);
        }

        return self::reorder($styles, static function (int $a, int $b) use ($depths, $scores, $priorities): int {
            $selectors = $depths[$a] === $depths[$b] ? $scores[$a] - $scores[$b] : $depths[$a] - $depths[$b];

            return $selectors ?: $priorities[$a] - $priorities[$b];
        });
    }

    /**
     * @param list<AtomicStyle> $styles
     *
     * @return list<AtomicStyle>
     */
    private static function sortByAtRules(array $styles): array
    {
        $priorities = self::priorities($styles);
        $flattened = [];
        foreach ($styles as $index => $style) {
            $flattened[$index] = self::flatten($style->conditions);
        }

        return self::reorder($styles, static function (int $a, int $b) use ($flattened, $priorities): int {
            return self::compareFlattened($flattened[$a], $flattened[$b]) ?: $priorities[$a] - $priorities[$b];
        });
    }

    /**
     * @param list<AtomicStyle>      $styles
     * @param callable(int, int):int $compare
     *
     * @return list<AtomicStyle>
     */
    private static function reorder(array $styles, callable $compare): array
    {
        $order = array_keys($styles);
        usort($order, $compare);

        return array_map(static fn (int $index): AtomicStyle => $styles[$index], $order);
    }

    /**
     * @param list<AtomicStyle> $styles
     *
     * @return list<int>
     */
    private static function priorities(array $styles): array
    {
        $priorities = [];
        foreach ($styles as $style) {
            $priorities[] = PropertyPriority::get($style->entry->property);
        }

        return $priorities;
    }

    /**
     * @param list<Condition> $a
     * @param list<Condition> $b
     */
    public static function compareAtRuleOrMixed(array $a, array $b): int
    {
        return self::compareFlattened(self::flatten($a), self::flatten($b));
    }

    /**
     * @param list<Condition> $a
     * @param list<Condition> $b
     */
    private static function compareFlattened(array $a, array $b): int
    {
        $max = max(\count($a), \count($b));
        for ($i = 0; $i < $max; ++$i) {
            $conditionA = $a[$i] ?? null;
            $conditionB = $b[$i] ?? null;
            if (null === $conditionA) {
                return -1;
            }
            if (null === $conditionB) {
                return 1;
            }
            if ($conditionA->isAtRule() && $conditionB->isNesting()) {
                return 1;
            }
            if ($conditionA->isNesting() && $conditionB->isAtRule()) {
                return -1;
            }
            if ($conditionA->isAtRule() && $conditionB->isAtRule()) {
                $atRuleA = $conditionA->params ?? (\is_string($conditionA->raw) ? $conditionA->raw : '');
                $atRuleB = $conditionB->params ?? (\is_string($conditionB->raw) ? $conditionB->raw : '');
                if ('' === $atRuleA) {
                    return -1;
                }
                if ('' === $atRuleB) {
                    return 1;
                }
                $score = AtRuleSorter::compare($atRuleA, $atRuleB);
                if (0 !== $score) {
                    return $score;
                }
                continue;
            }
            if ($conditionA->isNesting() && $conditionB->isNesting() && isset($a[$i + 1]) === isset($b[$i + 1])) {
                $score = self::pseudoScore($conditionA->value) - self::pseudoScore($conditionB->value);
                if (0 !== $score) {
                    return $score;
                }
            }
        }

        return 0;
    }

    /**
     * @param list<Condition> $conditions
     */
    private static function hasAtRule(array $conditions): bool
    {
        foreach ($conditions as $condition) {
            if (\in_array($condition->type, [Condition::AT_RULE, Condition::MIXED, Condition::MULTI_BLOCK], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<Condition> $conditions
     *
     * @return list<Condition>
     */
    private static function flatten(array $conditions): array
    {
        $flattened = [];
        foreach ($conditions as $condition) {
            match ($condition->type) {
                Condition::MIXED => array_push($flattened, ...$condition->value),
                Condition::MULTI_BLOCK => array_map(static function (Condition $block) use (&$flattened): void {
                    array_push($flattened, ...$block->value);
                }, $condition->value),
                default => $flattened[] = $condition,
            };
        }

        return $flattened;
    }

    /**
     * @param string|list<Condition> $selector
     */
    private static function pseudoScore(string|array $selector): int
    {
        if (!\is_string($selector)) {
            return 0;
        }
        foreach (self::PSEUDO_ORDER as $index => $pseudoClass) {
            if (str_contains($selector, $pseudoClass)) {
                return $index + 1;
            }
        }

        return 0;
    }
}
