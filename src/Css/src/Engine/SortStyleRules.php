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

        usort($withSelectorsOnly, static function (AtomicStyle $a, AtomicStyle $b): int {
            return self::compareSelectors($a, $b) ?: self::compareProperties($a, $b);
        });
        usort($withAtRules, static function (AtomicStyle $a, AtomicStyle $b): int {
            return self::compareAtRuleOrMixed($a->conditions, $b->conditions) ?: self::compareProperties($a, $b);
        });
        usort($declarations, self::compareProperties(...));

        return [...$declarations, ...$withSelectorsOnly, ...$withAtRules];
    }

    /**
     * @param list<Condition> $a
     * @param list<Condition> $b
     */
    public static function compareAtRuleOrMixed(array $a, array $b): int
    {
        $a = self::flatten($a);
        $b = self::flatten($b);
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

    private static function compareSelectors(AtomicStyle $a, AtomicStyle $b): int
    {
        if (\count($a->conditions) === \count($b->conditions)) {
            return self::pseudoScore($a->conditions[0]->value) - self::pseudoScore($b->conditions[0]->value);
        }

        return \count($a->conditions) - \count($b->conditions);
    }

    private static function compareProperties(AtomicStyle $a, AtomicStyle $b): int
    {
        if ($a->entry->property === $b->entry->property) {
            return 0;
        }

        return PropertyPriority::get($a->entry->property) - PropertyPriority::get($b->entry->property);
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
