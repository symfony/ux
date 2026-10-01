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
 * Port of Panda's `Breakpoints` (packages/core/src/breakpoints.ts).
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class Breakpoints
{
    /**
     * @var list<string>
     */
    public readonly array $keys;

    /**
     * @var array<string, array{min: string, max: ?string}>
     */
    private readonly array $values;

    /**
     * @param array<string, string> $breakpoints minimum widths, indexed by name
     */
    public function __construct(array $breakpoints)
    {
        uasort($breakpoints, static fn (string $a, string $b): int => (int) $a <=> (int) $b);

        $values = [];
        $names = array_map(strval(...), array_keys($breakpoints));
        foreach ($names as $index => $name) {
            $next = $breakpoints[$names[$index + 1] ?? null] ?? null;
            $values[$name] = [
                'min' => Units::toRem($breakpoints[$name]),
                'max' => null !== $next ? self::adjust($next) : null,
            ];
        }

        $this->values = $values;
        $this->keys = ['base', ...$names];
    }

    /**
     * @return list<string>
     */
    public function getConditionNames(): array
    {
        return array_keys($this->getRanges());
    }

    /**
     * @return array<string, Condition>
     */
    public function getConditions(): array
    {
        $conditions = [];
        foreach ($this->getRanges() as $name => $params) {
            $conditions[$name] = new Condition(Condition::AT_RULE, $name, '@media '.$params, 'breakpoint', $params);
        }

        return $conditions;
    }

    /**
     * @return array<string, string> media query params, indexed by condition name
     */
    private function getRanges(): array
    {
        $names = array_keys($this->values);
        $ranges = [];
        foreach ($names as $name) {
            $value = $this->values[$name];
            $ranges[(string) $name] = self::build($value['min'], null);
            $ranges[$name.'Only'] = self::build($value['min'], $value['max']);
            $ranges[$name.'Down'] = self::build(null, self::adjust($value['min']));
        }
        foreach ($names as $index => $min) {
            foreach (\array_slice($names, $index + 1) as $max) {
                $maxWidth = self::adjust($this->values[$max]['min']);
                $ranges[$min.'To'.ucfirst((string) $max)] = self::build($this->values[$min]['min'], $maxWidth);
            }
        }

        return array_filter($ranges, static fn (string $params): bool => '' !== $params);
    }

    private static function build(?string $min, ?string $max): string
    {
        if (null === $min && null === $max) {
            return '';
        }

        return implode(' and ', array_filter([
            'screen',
            null !== $min ? '(min-width: '.$min.')' : null,
            null !== $max ? '(max-width: '.$max.')' : null,
        ]));
    }

    private static function adjust(string $value): string
    {
        return Units::toRem(JsValue::toString(Units::parseFloat(Units::toPx($value)) - 0.04).'px');
    }
}
