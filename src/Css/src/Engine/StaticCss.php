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
 * Port of the `css` rules of Panda's `StaticCss` (packages/core/src/static-css.ts): the style hashes of values
 * no template writes as is, so that css() can give them at runtime.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class StaticCss
{
    public function __construct(
        private readonly Engine $engine,
    ) {
    }

    /**
     * @param list<array{properties: array<string, list<string|int|float>>, conditions?: list<string>, responsive?: bool}> $rules
     *
     * @return list<array<string, mixed>>
     */
    public function styles(array $rules): array
    {
        $config = $this->engine->config();
        $breakpoints = array_map(strval(...), array_keys($config['theme']['breakpoints'] ?? []));

        $styles = [];
        foreach ($rules as $rule) {
            $conditions = $rule['conditions'] ?? [];
            if ($rule['responsive'] ?? false) {
                $conditions = [...$conditions, ...$breakpoints];
            }

            foreach ($rule['properties'] as $property => $values) {
                foreach ($this->expand((string) $property, $values) as $value) {
                    $style = [] !== $conditions ? $this->conditionalValue($conditions, $value) : $value;
                    $styles[] = [$property => $style];
                }
            }
        }

        return $styles;
    }

    /**
     * @param list<string|int|float> $values
     *
     * @return list<string|int|float>
     */
    private function expand(string $property, array $values): array
    {
        $expanded = [];
        foreach ($values as $value) {
            if ('*' === $value) {
                $utilities = $this->engine->utilities();
                $keys = $utilities->getPropertyKeys($utilities->resolveShorthand($property));
                array_push($expanded, ...$keys);
            } else {
                $expanded[] = $value;
            }
        }

        return $expanded;
    }

    /**
     * @param list<string> $conditions
     *
     * @return array<string, string|int|float>
     */
    private function conditionalValue(array $conditions, string|int|float $value): array
    {
        $configured = $this->engine->config()['conditions'] ?? [];

        $conditional = ['base' => $value];
        foreach ($conditions as $condition) {
            $key = \array_key_exists($condition, $configured) ? '_'.$condition : $condition;
            $conditional[$key] = $value;
        }

        return $conditional;
    }
}
