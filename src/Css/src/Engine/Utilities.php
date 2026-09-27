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

use Symfony\UX\Css\Exception\UnsupportedStyleException;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class Utilities
{
    /**
     * @var array<string, Utility>
     */
    private array $utilities = [];

    /**
     * @var array<string, string>
     */
    private array $shorthands = [];

    /**
     * @var array<string, (\Closure(string|int|float|bool, TransformArgs): ?array<string, mixed>)|null>
     */
    private array $transforms = [];

    public readonly Tokens $tokens;

    /**
     * @param array<string, array<string, mixed>>                                                  $config     the `utilities` section of a Panda config
     * @param array<string, \Closure(string|int|float|bool, TransformArgs): ?array<string, mixed>> $transforms the transforms of the utilities whose config only names one
     */
    public function __construct(
        array $config,
        private readonly string $separator = '_',
        ?Tokens $tokens = null,
        array $transforms = [],
    ) {
        $this->tokens = $tokens ?? new Tokens();

        foreach ($config as $property => $utility) {
            $shorthands = (array) ($utility['shorthand'] ?? []);
            $this->utilities[$property] = new Utility(
                $property,
                $utility['className'] ?? null,
                $shorthands,
                $utility['values'] ?? null,
                $utility['layer'] ?? null,
            );
            foreach ($shorthands as $shorthand) {
                $this->shorthands[$shorthand] = $property;
            }

            if (isset($utility['transform'])) {
                $transform = $utility['transform'];
                $this->transforms[$property] = match (true) {
                    $transform instanceof \Closure => $transform,
                    isset($transform['colorMix']) => self::colorMixTransform($transform['colorMix']),
                    default => $transforms[$property] ?? null,
                };
            }
        }

        $palettes = $this->tokens->getColorPaletteNames();
        if ([] !== $palettes) {
            $this->utilities['colorPalette'] = new Utility('colorPalette', null, [], $palettes);
            $this->transforms['colorPalette'] = function (string|int|float|bool $value): ?array {
                return $this->tokens->getColorPalette(JsValue::toString($value));
            };
        }
    }

    /**
     * Port of `createColorMixTransform()` (packages/preset-base/src/color-mix-transform.ts).
     *
     * @return \Closure(string|int|float|bool, TransformArgs): array<string, mixed>
     */
    public static function colorMixTransform(string $property): \Closure
    {
        return static function (string|int|float|bool $value, TransformArgs $args) use ($property): array {
            $mix = $args->colorMix($value);
            if ($mix['invalid']) {
                return [$property => $value];
            }

            return ['--mix-'.$property => $mix['value'], $property => 'var(--mix-'.$property.', '.$mix['color'].')'];
        };
    }

    public function resolveShorthand(string $property): string
    {
        return $this->shorthands[$property] ?? $property;
    }

    public function get(string $property): ?Utility
    {
        return $this->utilities[$this->resolveShorthand($property)] ?? null;
    }

    /**
     * Port of `Utility::getPropertyKeys()`: every value a utility declares, like the `*` of Panda's staticCss expands to.
     *
     * @return list<string>
     */
    public function getPropertyKeys(string $property): array
    {
        $values = $this->utilities[$property]->values ?? null;

        if (\is_string($values)) {
            $keys = array_keys($this->tokens->getCategoryValues($values) ?? []);
        } elseif (\is_array($values) && isset($values['__function'])) {
            $keys = [];
            foreach ($values['probe'] ?? [] as $key => $value) {
                if (!str_starts_with((string) $key, '__category:')) {
                    $keys[] = $key;
                    continue;
                }

                $category = substr((string) $key, \strlen('__category:'));
                array_push($keys, ...array_keys($this->tokens->getCategoryValues($category) ?? []));
            }
        } elseif (\is_array($values) && array_is_list($values)) {
            $keys = $values;
        } elseif (\is_array($values)) {
            $keys = array_keys($values);
        } else {
            $keys = [];
        }

        return JsValue::objectKeys(array_keys(array_flip(array_map(strval(...), $keys))));
    }

    public function getClassName(string $property): string
    {
        return $this->get($property)?->className ?? PropertyName::toCss($this->resolveShorthand($property));
    }

    /**
     * Port of `Utility::transform()` (packages/core/src/utility.ts).
     *
     * @return array{className: string, styles: array<string, mixed>, layer?: string}
     *
     * @throws UnsupportedStyleException when the utility relies on a transform that is not ported
     */
    public function transform(string $property, string|int|float|bool $value): array
    {
        $property = $this->resolveShorthand($property);
        $utility = $this->utilities[$property] ?? null;

        $styleValue = \is_string($value) ? self::arbitraryValue($value) : $value;
        if (\is_string($styleValue)) {
            $styleValue = $this->tokens->expandReferences($styleValue);
        }

        $classNamePrefix = '' !== (string) $utility?->className ? $utility->className : PropertyName::toCss($property);
        $classNameValue = \is_string($value) ? str_replace(' ', '_', $value) : JsValue::toString($value);
        $className = $classNamePrefix.$this->separator.$classNameValue;
        $raw = null !== $utility ? $this->getRawValue($utility, $styleValue) : $styleValue;

        if (\array_key_exists($property, $this->transforms)) {
            $transform = $this->transforms[$property];
            if (null === $transform) {
                $message = \sprintf('The "%s" utility relies on a transform, which is not supported yet.', $property);

                throw new UnsupportedStyleException($message);
            }
            $styles = $transform($raw, new TransformArgs($styleValue, $this->tokens)) ?? [];
        } else {
            $var = null;
            if (str_starts_with($property, '--') && \is_string($raw)) {
                $var = $this->tokens->getVar($raw);
            }
            $styles = [$property => \is_string($var) ? $var : $raw];
        }

        $transformed = ['className' => $className, 'styles' => $styles];
        if (null !== $utility?->layer && '' !== $utility->layer) {
            $transformed['layer'] = $utility->layer;
        }

        return $transformed;
    }

    /**
     * Port of `Utility::getPropertyRawValue()`.
     */
    private function getRawValue(Utility $utility, string|int|float|bool $value): string|int|float|bool
    {
        $values = $utility->values;
        if (!JsValue::isTruthy($values) || (\is_array($values) && array_is_list($values))) {
            return $value;
        }
        if (\is_string($values)) {
            $raw = $this->tokens->getCategoryValues($values)[JsValue::toString($value)] ?? null;

            return JsValue::isTruthy($raw) ? $raw : $value;
        }
        if (!\is_array($values)) {
            return $value;
        }

        if (isset($values['__function'])) {
            if (!isset($values['probe'])) {
                $message = \sprintf(
                    'The "%s" utility computes its values with a function, which is not supported yet.',
                    $utility->property,
                );

                throw new UnsupportedStyleException($message);
            }

            $computed = [];
            foreach ($values['probe'] as $key => $entry) {
                if (str_starts_with($key, '__category:')) {
                    $categoryValues = $this->tokens->getCategoryValues(substr($key, \strlen('__category:'))) ?? [];
                    $computed = array_replace($computed, $categoryValues);
                } else {
                    $computed = array_replace($computed, [$key => $entry]);
                }
            }
            $values = $computed;
        } elseif (JsValue::isTruthy($values['type'] ?? null)) {
            return $value;
        }

        $raw = $values[JsValue::toString($value)] ?? null;

        return JsValue::isTruthy($raw) ? $raw : $value;
    }

    /**
     * Port of `getArbitraryValue()` (packages/shared/src/arbitrary-value.ts).
     */
    private static function arbitraryValue(string $value): string
    {
        $value = JsValue::trim($value);
        if (!str_starts_with($value, '[') || !str_ends_with($value, ']')) {
            return $value;
        }

        $inner = substr($value, 1, -1);
        $depth = 0;
        foreach (str_split($inner) as $char) {
            if ('[' === $char) {
                ++$depth;
            } elseif (']' === $char) {
                if (0 === $depth) {
                    return $value;
                }
                --$depth;
            }
        }

        return 0 === $depth ? JsValue::trim($inner) : $value;
    }
}
