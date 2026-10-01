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

use Symfony\UX\Css\Engine\Css\AtRule;
use Symfony\UX\Css\Engine\Css\Declaration;
use Symfony\UX\Css\Engine\Css\Node;
use Symfony\UX\Css\Engine\Css\Rule;

/**
 * Port of `generateTokenCss()` (packages/generator/src/artifacts/css/token-css.ts): every token as a CSS variable,
 * under the variable root, or under the selectors and at-rules of the conditions of a semantic token.
 *
 * Panda nests the rules with postcss-nested; the same result is computed here directly: at-rules wrap the rule,
 * selectors are resolved against their parent's `&`, and the variable root is removed from compound selectors.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class TokenCss
{
    public function __construct(
        private readonly Tokens $tokens,
        private readonly Conditions $conditions,
        private readonly string $root = ':where(:root, :host)',
    ) {
    }

    /**
     * @return list<Node> the content of the tokens layer
     */
    public function nodes(): array
    {
        $nodes = [];
        foreach ($this->tokens->getVars() as $condition => $vars) {
            if ([] === $vars) {
                continue;
            }

            if ('base' === $condition) {
                $nodes[] = new Rule($this->root, self::declarations($vars));
            } else {
                array_push($nodes, ...$this->conditionalRules((string) $condition, $vars));
            }
        }

        return $nodes;
    }

    /**
     * Port of `stringifyVars()`.
     *
     * @param array<string, mixed> $vars
     *
     * @return list<Node>
     */
    private function conditionalRules(string $conditionKey, array $vars): array
    {
        $combinations = [[]];
        foreach (explode(':', $conditionKey) as $key) {
            $raw = $this->conditions->get($key);
            $paths = null !== $raw ? self::selectorPaths($raw) : [];
            if ([] === $paths) {
                return [];
            }

            $next = [];
            foreach ($combinations as $partial) {
                foreach ($paths as $path) {
                    $next[] = [...$partial, ...$path];
                }
            }
            $combinations = $next;
        }

        $rules = [];
        foreach ($combinations as $segments) {
            $atRules = [];
            $selectors = [''];
            foreach ($segments as $segment) {
                if (str_starts_with($segment, '@')) {
                    $atRules[] = $segment;
                    $selectors = self::nest($selectors, $this->root.'&');
                } elseif ('' !== $segment) {
                    $selectors = self::nest($selectors, self::transformSegment($segment));
                }
            }

            $node = new Rule($this->withoutRoot(implode(', ', $selectors)), self::declarations($vars));
            foreach (array_reverse($atRules) as $atRule) {
                preg_match('/^@(\S+)\s*(.*)$/s', $atRule, $matches);
                $node = new AtRule($matches[1], $matches[2], [$node]);
            }
            $rules[] = $node;
        }

        return $rules;
    }

    /**
     * Port of `getSelectorPaths()`: a string gives one path, a mixed condition its last part, a multi-block condition one path per `@slot`.
     *
     * @param string|array<array-key, mixed> $condition
     *
     * @return list<list<string>>
     */
    private static function selectorPaths(string|array $condition): array
    {
        if (\is_string($condition)) {
            return [[$condition]];
        }
        if (array_is_list($condition)) {
            $last = end($condition);

            return \is_string($last) && '' !== $last ? [[$last]] : [];
        }

        $paths = [];
        $walk = static function (array $node, array $path) use (&$walk, &$paths): void {
            foreach ($node as $key => $value) {
                if ('@slot' === $value) {
                    $paths[] = [...$path, (string) $key];
                } elseif (\is_array($value)) {
                    $walk($value, [...$path, (string) $key]);
                }
            }
        };
        $walk($condition, []);

        return $paths;
    }

    /**
     * Port of `transformSegment()` and `extractParentSelectors()` (packages/core/src/selector.ts): `.dark &` becomes `&.dark`.
     */
    private static function transformSegment(string $segment): string
    {
        $parents = [];
        foreach (new Rule($segment)->selectors() as $selector) {
            if (Condition::PARENT_NESTING === ConditionParser::parse($selector)?->type) {
                $parents[trim(preg_replace('/\s&/', '', $selector))] = true;
            }
        }
        if ([] === $parents) {
            return $segment;
        }

        $parent = implode(', ', array_keys($parents));

        return '&'.(\count($parents) > 1 ? ':where('.$parent.')' : $parent);
    }

    /**
     * Resolves a nested selector like postcss-nested: `&` stands for the parent, a selector without `&` descends from it.
     *
     * @param list<string> $parents
     *
     * @return list<string>
     */
    private static function nest(array $parents, string $child): array
    {
        $selectors = [];
        foreach ($parents as $parent) {
            foreach (new Rule($child)->selectors() as $selector) {
                if (str_contains($selector, '&')) {
                    $selectors[] = str_replace('&', $parent, $selector);
                } elseif ('' === $parent) {
                    $selectors[] = $selector;
                } else {
                    $selectors[] = $parent.' '.$selector;
                }
            }
        }

        return $selectors;
    }

    /**
     * Port of `cleanupSelectors()`: `:where(html).dark` becomes `.dark`.
     */
    private function withoutRoot(string $selector): string
    {
        $selectors = new Rule($selector)->selectors();
        if (implode(', ', $selectors) === $this->root || implode(',', $selectors) === $this->root) {
            return $selector;
        }

        $cleaned = [];
        foreach ($selectors as $part) {
            $pieces = array_filter(explode($this->root, $part), static fn (string $piece): bool => '' !== $piece);
            $withoutRoot = implode('', $pieces);
            if ('' !== $withoutRoot) {
                $cleaned[] = $withoutRoot;
            }
        }

        return [] === $cleaned ? $selector : implode(', ', $cleaned);
    }

    /**
     * @param array<string, mixed> $vars
     *
     * @return list<Declaration>
     */
    private static function declarations(array $vars): array
    {
        $declarations = [];
        foreach ($vars as $name => $value) {
            $declarations[] = new Declaration($name, \is_string($value) ? $value : JsValue::toString($value));
        }

        return $declarations;
    }
}
