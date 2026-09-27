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
 * Port of Panda's `parseCondition()` (packages/core/src/parse-condition.ts).
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class ConditionParser
{
    /**
     * @param string|array<array-key, mixed> $condition a query, a list of queries, or an object with `@slot` leaves
     */
    public static function parse(string|array $condition): ?Condition
    {
        if (\is_array($condition) && array_is_list($condition)) {
            $parsed = array_map(static function (mixed $part): ?Condition {
                return \is_string($part) || \is_array($part) ? self::parse($part) : null;
            }, $condition);
            $parts = array_values(array_filter($parsed));

            return new Condition(Condition::MIXED, $parts, $condition);
        }
        if (\is_array($condition)) {
            return self::parseObject($condition);
        }

        if (str_starts_with($condition, '@')) {
            preg_match('/^@([^\s(]*)\s*(.*)$/s', $condition, $matches);
            $params = rtrim($matches[2]);

            return new Condition(Condition::AT_RULE, $params, $condition, $matches[1], $params);
        }

        $type = match (true) {
            str_starts_with($condition, '&') => Condition::SELF_NESTING,
            str_ends_with($condition, ' &') => Condition::PARENT_NESTING,
            str_contains($condition, '&') => Condition::COMBINATOR_NESTING,
            default => null,
        };

        return null === $type ? null : new Condition($type, $condition, $condition);
    }

    /**
     * @param array<string, mixed> $object
     */
    private static function parseObject(array $object): ?Condition
    {
        $blocks = [];
        $traverse = static function (array $node, array $path) use (&$traverse, &$blocks): void {
            foreach ($node as $key => $value) {
                if ('@slot' === $value) {
                    $parsed = self::parse([...$path, (string) $key]);
                    if (null !== $parsed && Condition::MIXED === $parsed->type && [] !== $parsed->value) {
                        $blocks[] = $parsed;
                    }
                } elseif (\is_array($value)) {
                    $traverse($value, [...$path, (string) $key]);
                }
            }
        };
        $traverse($object, []);

        return match (\count($blocks)) {
            0 => null,
            1 => $blocks[0],
            default => new Condition(Condition::MULTI_BLOCK, $blocks, $object),
        };
    }
}
