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
use Symfony\UX\Css\Engine\Css\Root;
use Symfony\UX\Css\Engine\Css\Rule;
use Symfony\UX\Css\Exception\UnsupportedStyleException;

/**
 * Port of Panda's `stringify()` (packages/core/src/stringify.ts), building the tree postcss would parse from its output.
 *
 * A block opens on the first declaration written inside it, and a nested object closes the selector block around it,
 * so declarations written after a nested object land in a new rule, like in Panda.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class StyleStringifier
{
    /**
     * @param array<string, mixed> $style
     *
     * @return list<Css\Node>
     */
    public static function stringify(array $style): array
    {
        $root = new Root();
        self::parse($style, null, [], $root);
        $nodes = $root->nodes;
        foreach ($nodes as $node) {
            $node->remove();
        }

        return $nodes;
    }

    /**
     * @param array<array-key, mixed> $style
     * @param list<\stdClass>         $conditions
     */
    private static function parse(array $style, ?\stdClass $selectors, array $conditions, Root $root): void
    {
        foreach ($style as $name => $value) {
            $name = (string) $name;
            $isAtRuleLike = str_starts_with($name, '@');
            $isVariableLike = !$isAtRuleLike && str_starts_with($name, '--');

            $values = [$value];
            if ($isAtRuleLike && \is_array($value) && array_is_list($value) && [] !== $value) {
                $values = $value;
            }

            foreach ($values as $data) {
                if (!\is_array($data) || ([] !== $data && array_is_list($data))) {
                    self::write($selectors, $conditions, $name, $data, $isAtRuleLike, $isVariableLike, $root);
                    continue;
                }

                if (null !== $selectors) {
                    $selectors->rule = null;
                }

                if ($isAtRuleLike) {
                    $nextSelectors = $selectors;
                    $condition = (object) ['name' => $name, 'node' => null];
                    self::parse($data, $nextSelectors, [...$conditions, $condition], $root);
                } else {
                    $nested = Selectors::parse($name);
                    $resolved = $nested;
                    if (null !== $selectors && [] !== $selectors->selectors) {
                        $resolved = Selectors::resolve($selectors->selectors, $nested);
                    }
                    $nextSelectors = (object) ['selectors' => $resolved, 'rule' => null];
                    self::parse($data, $nextSelectors, $conditions, $root);
                }

                if (null !== $nextSelectors) {
                    $nextSelectors->rule = null;
                }
            }
        }
    }

    /**
     * @param list<\stdClass> $conditions
     */
    private static function write(
        ?\stdClass $selectors,
        array $conditions,
        string $name,
        mixed $data,
        bool $isAtRuleLike,
        bool $isVariableLike,
        Root $root,
    ): void {
        if (false === $data) {
            return;
        }
        if ($isAtRuleLike) {
            throw new UnsupportedStyleException(\sprintf('At-rule statements like "%s" are not supported.', $name));
        }

        $container = $root;
        foreach ($conditions as $condition) {
            if (null === $condition->node) {
                preg_match('/^@([^\s(]*)(\s*)(.*)$/s', $condition->name, $matches);
                $condition->node = new AtRule($matches[1], rtrim($matches[3]));
                $condition->node->afterName = '' !== $matches[2] ? $matches[2] : ' ';
                $container->append($condition->node);
            }
            $container = $condition->node;
        }

        if (null !== $selectors && [] !== $selectors->selectors) {
            if (null === $selectors->rule) {
                $ruleSelectors = array_map(
                    static fn (string $selector): string => preg_replace('/& /', '', $selector, 1),
                    $selectors->selectors,
                );
                $selectors->rule = new Rule(implode(',', $ruleSelectors));
                $container->append($selectors->rule);
            }
            $container = $selectors->rule;
        }

        $value = match (true) {
            \is_int($data), \is_float($data) => JsValue::toString($data).self::unit($name, $data, $isVariableLike),
            \is_bool($data) => $data ? 'true' : 'false',
            \is_array($data) => implode(',', array_map(
                static fn (mixed $item): string => \is_scalar($item) ? JsValue::toString($item) : '',
                $data,
            )),
            null === $data => 'null',
            default => (string) $data,
        };

        $important = false;
        if (preg_match('/^(.*?)\s*!\s*important\s*$/is', $value, $matches)) {
            $value = $matches[1];
            $important = true;
        }

        $property = $isVariableLike ? $name : PropertyName::toCss($name);
        $container->append(new Declaration($property, trim($value), $important));
    }

    private static function unit(string $name, int|float $number, bool $isVariableLike): string
    {
        return 0 == $number || Unitless::has($name) || $isVariableLike ? '' : 'px';
    }
}
