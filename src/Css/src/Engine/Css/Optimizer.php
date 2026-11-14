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

use Symfony\UX\Css\Engine\AtRuleSorter;

/**
 * The postcss passes of Panda's `Stylesheet::toCss()`, in their execution order.
 *
 * Panda's `prettify` is a plain function plugin, so postcss runs it before the `OnceExit` plugins
 * (discard-duplicates, merge-rules, discard-empty): a merged rule keeps the whitespace it had before.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class Optimizer
{
    public function toCss(Root $root): string
    {
        return $this->optimize($root)->toString();
    }

    public function optimize(Root $root): Root
    {
        $this->sortMediaQueries($root);
        $this->prettify($root);
        if ([] !== $root->nodes) {
            $root->nodes[0]->before = '';
        }
        $this->discardDuplicates($root);
        new MergeRules()->process($root);
        $root->each($this->discardEmpty(...));

        return $root;
    }

    private function sortMediaQueries(Container $container): void
    {
        usort($container->nodes, static function (Node $a, Node $b): int {
            $aIsMedia = $a instanceof AtRule && \in_array($a->name, ['media', 'container'], true);
            $bIsMedia = $b instanceof AtRule && \in_array($b->name, ['media', 'container'], true);

            return match (true) {
                $aIsMedia && $bIsMedia => AtRuleSorter::compare($a->params, $b->params),
                $aIsMedia => 1,
                $bIsMedia => -1,
                default => 0,
            };
        });

        foreach ($container->nodes as $node) {
            if ($node instanceof Container) {
                $this->sortMediaQueries($node);
            }
        }
    }

    private function prettify(Container $container, int $indent = 0): void
    {
        foreach ($container->nodes as $index => $node) {
            if ('' === trim($node->before) || str_contains($node->before, "\n")) {
                $node->before = "\n".(!$container instanceof Rule && $index > 0 ? "\n" : '').str_repeat('  ', $indent);
            }
            if ($node instanceof Container) {
                $this->prettify($node, $indent + 1);
            }
        }
    }

    private function discardDuplicates(Container $container): void
    {
        $this->discardDuplicateDeclarations($container);

        $nodes = $container->nodes;
        $rulesOfSelector = [];
        $rankOfRule = [];
        foreach ($nodes as $node) {
            if ($node instanceof Rule) {
                $rankOfRule[spl_object_id($node)] = \count($rulesOfSelector[$node->selector] ?? []);
                $rulesOfSelector[$node->selector][] = $node;
            }
        }

        $index = \count($nodes) - 1;
        while ($index >= 0) {
            $last = $nodes[$index--];
            if (null === $last->parent) {
                continue;
            }
            if ($last instanceof Container) {
                $this->discardDuplicates($last);
            }
            if ($last instanceof Rule) {
                $this->dedupeRule($last, $rulesOfSelector[$last->selector], $rankOfRule[spl_object_id($last)]);
            } elseif ($last instanceof AtRule && 'layer' !== $last->name) {
                $this->dedupeNode($last, $container);
            }
        }
    }

    private function discardDuplicateDeclarations(Container $container): void
    {
        $lastOfKey = [];
        $duplicates = [];
        foreach ($container->nodes as $node) {
            if (!$node instanceof Declaration) {
                continue;
            }
            $key = $node->property."\0".$node->value."\0".trim($node->before)."\0".($node->important ? '1' : '0');
            if (isset($lastOfKey[$key])) {
                $duplicates[] = $lastOfKey[$key];
            }
            $lastOfKey[$key] = $node;
        }

        foreach ($duplicates as $duplicate) {
            $duplicate->remove();
        }
    }

    /**
     * @param list<Rule> $rulesOfSelector every sibling rule sharing the selector of $last, in document order
     * @param int        $rank            the offset of $last in $rulesOfSelector
     */
    private function dedupeRule(Rule $last, array $rulesOfSelector, int $rank): void
    {
        $index = $rank - 1;
        while ($index >= 0) {
            $node = $rulesOfSelector[$index--];
            if (null === $node->parent) {
                continue;
            }
            foreach ($last->nodes as $child) {
                if ($child instanceof Declaration) {
                    $this->dedupeNode($child, $node);
                }
            }
            if ([] === $node->nodes) {
                $node->remove();
            }
        }
    }

    private function dedupeNode(Node $last, Container $container): void
    {
        $position = $container->indexOf($last);
        $index = false !== $position ? $position - 1 : \count($container->nodes) - 1;
        while ($index >= 0) {
            $node = $container->nodes[$index--] ?? null;
            if (null !== $node && self::equals($node, $last)) {
                $node->remove();
            }
        }
    }

    private static function equals(Node $a, Node $b): bool
    {
        if ($a::class !== $b::class) {
            return false;
        }
        if ($a instanceof Declaration && $b instanceof Declaration) {
            return $a->important === $b->important
                && $a->property === $b->property
                && $a->value === $b->value
                && trim($a->before) === trim($b->before);
        }
        if ($a instanceof Rule && $b instanceof Rule && $a->selector !== $b->selector) {
            return false;
        }
        if (
            $a instanceof AtRule
            && $b instanceof AtRule
            && ($a->name !== $b->name || $a->params !== $b->params || trim($a->before) !== trim($b->before))
        ) {
            return false;
        }
        if ($a instanceof Container && $b instanceof Container) {
            if (\count($a->nodes) !== \count($b->nodes)) {
                return false;
            }
            foreach ($a->nodes as $index => $node) {
                if (!self::equals($node, $b->nodes[$index])) {
                    return false;
                }
            }
        }

        return true;
    }

    private function discardEmpty(Node $node): void
    {
        if ($node instanceof Container) {
            $node->each($this->discardEmpty(...));
        }

        if (
            ($node instanceof Declaration && '' === $node->value && !str_starts_with($node->property, '--'))
            || ($node instanceof Rule && '' === $node->selector)
            || ($node instanceof Container && [] === $node->nodes)
        ) {
            $node->remove();
        }
    }
}
