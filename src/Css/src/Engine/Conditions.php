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
 * Port of Panda's `Conditions` (packages/core/src/conditions.ts), plus the runtime sort of `styled-system/css/conditions.mjs`.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class Conditions
{
    /**
     * @var array<string, ?Condition>
     */
    private readonly array $values;

    /**
     * @param array<string, mixed>  $conditions     the `conditions` section of a Panda config
     * @param array<string, string> $containerSizes
     * @param list<string>          $containerNames
     * @param array<string, mixed>  $themes
     */
    public function __construct(
        array $conditions,
        public readonly Breakpoints $breakpoints,
        array $containerSizes = [],
        array $containerNames = [],
        array $themes = [],
    ) {
        $values = [];
        foreach ($conditions as $name => $condition) {
            $isParsable = \is_string($condition) || \is_array($condition);
            $values['_'.$name] = $isParsable ? ConditionParser::parse($condition) : null;
        }
        foreach ($breakpoints->getConditions() as $name => $condition) {
            $values[$name] = $condition;
        }
        foreach (['', ...$containerNames] as $containerName) {
            foreach ($containerSizes as $size => $value) {
                $rem = Units::toRem((string) $value);
                $values['@'.$containerName.'/'.$size] = new Condition(
                    Condition::AT_RULE,
                    $rem,
                    '@container '.$containerName.' (min-width: '.$rem.')',
                    'container',
                    $containerName.' '.$value,
                );
            }
        }
        foreach ($themes as $theme => $variant) {
            $values['_theme'.ucfirst($theme)] = ConditionParser::parse('[data-panda-theme='.$theme.'] &');
        }

        $this->values = $values;
    }

    /**
     * @return list<string>
     */
    public function getNames(): array
    {
        return array_map(strval(...), array_keys($this->values));
    }

    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->values);
    }

    public function isCondition(string $key): bool
    {
        return 'base' === $key
            || \array_key_exists($key, $this->values)
            || str_starts_with($key, '@')
            || str_contains($key, '&');
    }

    /**
     * @return string|array<array-key, mixed>|null the condition as written in the config
     */
    public function get(string $key): string|array|null
    {
        return ($this->values[$key] ?? null)?->raw;
    }

    public function getRaw(string $key): ?Condition
    {
        return $this->values[$key] ?? ConditionParser::parse($key);
    }

    /**
     * @param list<string> $paths
     *
     * @return list<string>
     */
    public function finalize(array $paths): array
    {
        return array_map(function (string $path): string {
            if (\array_key_exists($path, $this->values) || 'base' === $path) {
                return preg_replace('/^_/', '', $path);
            }
            if (str_contains($path, '&') || str_contains($path, '@')) {
                return '['.str_replace(' ', '_', JsValue::trim($path)).']';
            }

            return $path;
        }, $paths);
    }

    /**
     * Build order: at-rules first, pseudo-elements last, source order otherwise.
     *
     * @param list<string> $conditions
     *
     * @return list<Condition>
     */
    public function sortDetails(array $conditions): array
    {
        $flattened = [];
        foreach ($conditions as $index => $key) {
            $condition = $this->getRaw($key);
            if (null === $condition) {
                continue;
            }
            foreach (Condition::MIXED === $condition->type ? $condition->value : [$condition] as $part) {
                $flattened[] = [$part, $index];
            }
        }

        usort($flattened, static function (array $a, array $b): int {
            [$conditionA, $indexA] = $a;
            [$conditionB, $indexB] = $b;
            if ($conditionA->isAtRule() !== $conditionB->isAtRule()) {
                return $conditionA->isAtRule() ? -1 : 1;
            }
            if ($conditionA->isPseudoElement() !== $conditionB->isPseudoElement()) {
                return $conditionA->isPseudoElement() ? 1 : -1;
            }

            return $indexA <=> $indexB;
        });

        return array_column($flattened, 0);
    }
}
