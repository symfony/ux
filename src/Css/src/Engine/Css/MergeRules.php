<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Engine\Css;

/**
 * Port of the postcss-merge-rules copy inlined in Panda (packages/core/src/plugins/merge-rules.ts).
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class MergeRules
{
    private const VENDOR_PREFIX = '/-(ah|apple|atsc|epub|hp|khtml|moz|ms|o|rim|ro|tc|wap|webkit|xv)-/';

    private ?Rule $cache = null;

    public function process(Root $root): void
    {
        $this->cache = null;
        $root->walkRules($this->merge(...));
    }

    private function merge(Rule $rule): void
    {
        if (null === $this->cache || !self::canMerge($rule, $this->cache)) {
            $this->cache = $rule;

            return;
        }
        if ($this->cache === $rule) {
            return;
        }

        self::mergeParents($this->cache, $rule);

        if (self::sameDeclarationsAndOrder($rule->declarations(), $this->cache->declarations())) {
            $rule->selector = $this->cache->selector.','.$rule->selector;
            $this->cache->remove();
            $this->cache = $rule;

            return;
        }

        if ($this->cache->selector === $rule->selector) {
            $cached = $this->cache->declarations();
            $cache = $this->cache;
            $rule->walk(static function (Node $node) use ($cached, $cache): void {
                if ($node instanceof Declaration && -1 !== self::indexOfDeclaration($cached, $node)) {
                    $node->remove();

                    return;
                }
                $cache->append($node);
            });
            $rule->remove();

            return;
        }

        $this->cache = self::partialMerge($this->cache, $rule);
    }

    private static function partialMerge(Rule $first, Rule $second): Rule
    {
        $intersection = self::intersect($first->declarations(), $second->declarations());
        if ([] === $intersection) {
            return $second;
        }

        $nextRule = $second->next();
        if (null === $nextRule) {
            $parentSibling = $second->parent?->next();
            $nextRule = $parentSibling instanceof Container ? ($parentSibling->nodes[0] ?? null) : null;
        }
        if ($nextRule instanceof Rule && self::canMerge($second, $nextRule)) {
            $nextIntersection = self::intersect($second->declarations(), $nextRule->declarations());
            if (\count($nextIntersection) > \count($intersection)) {
                self::mergeParents($second, $nextRule);
                $first = $second;
                $second = $nextRule;
                $intersection = $nextIntersection;
            }
        }

        $firstDeclarations = $first->declarations();
        $filtered = [];
        foreach ($intersection as $intersectIndex => $declaration) {
            $indexOfDeclaration = self::indexOfDeclaration($firstDeclarations, $declaration);
            $property = $declaration->property;
            $isConflicting = static fn (Declaration $d): bool => self::isConflictingProperty($d->property, $property);
            $laterInFirst = \array_slice($firstDeclarations, $indexOfDeclaration + 1);
            $nextConflictInFirst = array_values(array_filter($laterInFirst, $isConflicting));
            if ([] === $nextConflictInFirst) {
                $filtered[] = $declaration;
                continue;
            }
            $laterInIntersection = \array_slice($intersection, $intersectIndex + 1);
            $nextConflictInIntersection = array_values(array_filter($laterInIntersection, $isConflicting));
            if (\count($nextConflictInFirst) !== \count($nextConflictInIntersection)) {
                continue;
            }
            foreach ($nextConflictInFirst as $index => $conflict) {
                if (!$conflict->equals($nextConflictInIntersection[$index])) {
                    continue 2;
                }
            }
            $filtered[] = $declaration;
        }
        $intersection = $filtered;

        $secondDeclarations = $second->declarations();
        $filtered = [];
        foreach ($intersection as $declaration) {
            $nextConflictIndex = null;
            foreach ($secondDeclarations as $index => $candidate) {
                if (self::isConflictingProperty($candidate->property, $declaration->property)) {
                    $nextConflictIndex = $index;
                    break;
                }
            }
            if (null === $nextConflictIndex || !$secondDeclarations[$nextConflictIndex]->equals($declaration)) {
                continue;
            }
            if (!\in_array(strtolower($declaration->property), ['direction', 'unicode-bidi'], true)) {
                foreach ($secondDeclarations as $candidate) {
                    if ('all' === strtolower($candidate->property)) {
                        continue 2;
                    }
                }
            }
            array_splice($secondDeclarations, $nextConflictIndex, 1);
            $filtered[] = $declaration;
        }
        $intersection = $filtered;
        if ([] === $intersection) {
            return $second;
        }

        $receivingBlock = clone $second;
        $receivingBlock->selector = $first->selector.','.$second->selector;
        $receivingBlock->nodes = [];
        $second->parent->insertBefore($second, $receivingBlock);

        $firstClone = clone $first;
        $secondClone = clone $second;
        foreach ($firstClone->declarations() as $declaration) {
            if (-1 !== self::indexOfDeclaration($intersection, $declaration)) {
                $declaration->remove();
                $receivingBlock->append($declaration);
            }
        }
        foreach ($secondClone->declarations() as $declaration) {
            if (-1 !== self::indexOfDeclaration($intersection, $declaration)) {
                $declaration->remove();
            }
        }

        if (self::ruleLength($firstClone, $receivingBlock, $secondClone) < self::ruleLength($first, $second)) {
            $first->replaceWith($firstClone);
            $second->replaceWith($secondClone);
            foreach ([$firstClone, $receivingBlock, $secondClone] as $rule) {
                if ([] === $rule->nodes) {
                    $rule->remove();
                }
            }

            return null === $secondClone->parent ? $receivingBlock : $secondClone;
        }

        $receivingBlock->remove();

        return $second;
    }

    private static function canMerge(Rule $a, Rule $b): bool
    {
        $parent = self::sameParent($a, $b);
        if ($parent && $a->parent instanceof AtRule && str_contains($a->parent->name, 'keyframes')) {
            return false;
        }
        foreach ([...$a->nodes, ...$b->nodes] as $node) {
            if ($node instanceof Container) {
                return false;
            }
        }
        if (!$parent) {
            return false;
        }

        $selectors = [...$a->selectors(), ...$b->selectors()];
        foreach ($selectors as $selector) {
            if (preg_match(self::VENDOR_PREFIX, $selector)) {
                return self::sameVendor($a->selectors(), $b->selectors());
            }
        }

        return true;
    }

    /**
     * @param list<string> $a
     * @param list<string> $b
     */
    private static function sameVendor(array $a, array $b): bool
    {
        $vendorOf = static fn (string $s): string => preg_match(self::VENDOR_PREFIX, $s, $m) ? implode(',', $m) : '';
        $same = static fn (array $selectors): string => implode(',', array_map($vendorOf, $selectors));
        $isMsPlaceholder = static fn (string $s): bool => 1 === preg_match('/-ms-input-placeholder/i', $s);
        $msVendor = static fn (array $selectors): bool => [] !== array_filter($selectors, $isMsPlaceholder);

        return $same($a) === $same($b) && !($msVendor($a) && $msVendor($b));
    }

    private static function sameParent(Node $a, Node $b): bool
    {
        if (null === $a->parent) {
            return null === $b->parent;
        }
        if (null === $b->parent) {
            return false;
        }
        if ($a->parent instanceof AtRule && $b->parent instanceof AtRule) {
            if (
                $a->parent->params !== $b->parent->params
                || strtolower($a->parent->name) !== strtolower($b->parent->name)
            ) {
                return false;
            }
        } elseif ($a->parent::class !== $b->parent::class) {
            return false;
        }

        return self::sameParent($a->parent, $b->parent);
    }

    private static function mergeParents(Rule $first, Rule $second): void
    {
        if (null === $first->parent || null === $second->parent || $first->parent === $second->parent) {
            return;
        }

        $second->remove();
        $first->parent->append($second);
    }

    /**
     * @param list<Declaration> $a
     * @param list<Declaration> $b
     */
    private static function sameDeclarationsAndOrder(array $a, array $b): bool
    {
        if (\count($a) !== \count($b)) {
            return false;
        }
        foreach ($a as $index => $declaration) {
            if (!$declaration->equals($b[$index])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param list<Declaration> $declarations
     */
    private static function indexOfDeclaration(array $declarations, Declaration $declaration): int
    {
        foreach ($declarations as $index => $candidate) {
            if ($candidate->equals($declaration)) {
                return $index;
            }
        }

        return -1;
    }

    /**
     * @param list<Declaration> $a
     * @param list<Declaration> $b
     *
     * @return list<Declaration>
     */
    private static function intersect(array $a, array $b): array
    {
        $isInB = static fn (Declaration $declaration): bool => -1 !== self::indexOfDeclaration($b, $declaration);

        return array_values(array_filter($a, $isInB));
    }

    private static function ruleLength(Rule ...$rules): int
    {
        $css = '';
        foreach ($rules as $rule) {
            $css .= [] !== $rule->nodes ? $rule->toString() : '';
        }

        return preg_match_all('/./su', $css) + preg_match_all('/[\x{10000}-\x{10FFFF}]/u', $css);
    }

    /**
     * @return array{prefix: ?string, base: ?string, rest: list<string>}
     */
    private static function splitProperty(string $property): array
    {
        $parts = explode('-', $property);
        if ('-' !== ($property[0] ?? '')) {
            return ['prefix' => '', 'base' => $parts[0], 'rest' => \array_slice($parts, 1)];
        }
        if ('-' === ($property[1] ?? '')) {
            return ['prefix' => null, 'base' => null, 'rest' => [$property]];
        }

        return ['prefix' => $parts[1] ?? null, 'base' => $parts[2] ?? null, 'rest' => \array_slice($parts, 3)];
    }

    private static function isConflictingProperty(string $a, string $b): bool
    {
        if ($a === $b) {
            return true;
        }

        $a = self::splitProperty($a);
        $b = self::splitProperty($b);
        if (\in_array($a['base'], [null, ''], true) && \in_array($b['base'], [null, ''], true)) {
            return true;
        }
        if ($a['base'] !== $b['base'] && 'place' !== $a['base'] && 'place' !== $b['base']) {
            return false;
        }
        if (\count($a['rest']) !== \count($b['rest'])) {
            return true;
        }
        if (
            'border' === $a['base']
            && [] !== array_intersect(['image', 'width', 'color', 'style'], [...$a['rest'], ...$b['rest']])
        ) {
            return true;
        }

        foreach ($a['rest'] as $index => $part) {
            if (($b['rest'][$index] ?? null) !== $part) {
                return false;
            }
        }

        return true;
    }
}
