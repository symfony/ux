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

use Symfony\UX\Css\Exception\InvalidStyleException;
use Symfony\UX\Css\Exception\UnsupportedStyleException;

/**
 * Port of Panda's `cva()` resolution (packages/generator/src/artifacts/js/cva.ts).
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class CvaRecipe
{
    private const KEYS = ['base', 'variants', 'defaultVariants', 'compoundVariants'];

    /**
     * @param array<array-key, mixed>                                                              $base
     * @param array<array-key, array<array-key, array<array-key, mixed>>>                          $variants
     * @param array<array-key, string|int|float|bool>                                              $defaultVariants
     * @param list<array{css: array<array-key, mixed>, conditions: array<array-key, list<mixed>>}> $compoundVariants
     */
    private function __construct(
        private readonly string $id,
        private readonly array $base,
        private readonly array $variants,
        private readonly array $defaultVariants,
        private readonly array $compoundVariants,
    ) {
    }

    /**
     * @param array<array-key, mixed> $config
     *
     * @throws InvalidStyleException
     */
    public static function fromConfig(array $config): self
    {
        foreach ($config as $key => $value) {
            if (!\in_array($key, self::KEYS, true)) {
                $message = \sprintf('The cva() key "%s" is not supported, supported keys are "%s".', $key, implode('", "', self::KEYS));

                throw new InvalidStyleException($message);
            }
        }

        $base = self::styleHash($config['base'] ?? [], '"base"');

        $variants = [];
        $variantsConfig = $config['variants'] ?? [];
        if (!\is_array($variantsConfig)) {
            $message = \sprintf('The cva() "variants" must be a hash, got "%s".', get_debug_type($variantsConfig));

            throw new InvalidStyleException($message);
        }
        foreach ($variantsConfig as $name => $values) {
            $name = (string) $name;
            if (!\is_array($values)) {
                $message = \sprintf('The cva() variant "%s" must be a hash of values, got "%s".', $name, get_debug_type($values));

                throw new InvalidStyleException($message);
            }
            $variants[$name] = [];
            foreach ($values as $valueName => $hash) {
                $valueName = (string) $valueName;
                $variants[$name][$valueName] = self::styleHash($hash, '"'.$name.'.'.$valueName.'"');
            }
        }

        $defaultVariants = [];
        $defaultsConfig = $config['defaultVariants'] ?? [];
        if (!\is_array($defaultsConfig)) {
            $message = \sprintf('The cva() "defaultVariants" must be a hash, got "%s".', get_debug_type($defaultsConfig));

            throw new InvalidStyleException($message);
        }
        foreach ($defaultsConfig as $name => $value) {
            $name = (string) $name;
            self::assertDeclaredValue($variants, $name, $value, 'default variant');
            $defaultVariants[$name] = $value;
        }

        $compoundVariants = [];
        $compoundsConfig = $config['compoundVariants'] ?? [];
        if (!\is_array($compoundsConfig) || !array_is_list($compoundsConfig)) {
            $message = \sprintf('The cva() "compoundVariants" must be a list of hashes, got "%s".', get_debug_type($compoundsConfig));

            throw new InvalidStyleException($message);
        }
        foreach ($compoundsConfig as $index => $compound) {
            if (!\is_array($compound) || ([] !== $compound && array_is_list($compound))) {
                $message = \sprintf('The cva() "compoundVariants" must be a list of hashes, item %d is "%s".', $index, get_debug_type($compound));

                throw new InvalidStyleException($message);
            }
            if (!\array_key_exists('css', $compound)) {
                $message = \sprintf('The cva() compound variant %d has no "css" key.', $index);

                throw new InvalidStyleException($message);
            }

            $conditions = [];
            foreach ($compound as $name => $value) {
                $name = (string) $name;
                if ('css' === $name) {
                    continue;
                }
                $values = \is_array($value) && array_is_list($value) ? $value : [$value];
                foreach ($values as $item) {
                    self::assertDeclaredValue($variants, $name, $item, 'compound variant');
                }
                $conditions[$name] = $values;
            }

            $css = self::styleHash($compound['css'], '"css" of the compound variant '.$index);
            $compoundVariants[] = ['css' => $css, 'conditions' => $conditions];
        }

        return new self(hash('xxh128', serialize($config)), $base, $variants, $defaultVariants, $compoundVariants);
    }

    public function id(): string
    {
        return $this->id;
    }

    /**
     * @return list<array<array-key, mixed>>
     */
    public function styles(): array
    {
        $styles = [$this->base];
        foreach ($this->variants as $values) {
            foreach ($values as $hash) {
                $styles[] = $hash;
            }
        }
        foreach ($this->compoundVariants as $compound) {
            $styles[] = $compound['css'];
        }

        return $styles;
    }

    /**
     * @param array<array-key, mixed> $props
     */
    public function key(array $props): string
    {
        ['applied' => $applied, 'compounds' => $compounds] = $this->select($props);

        return json_encode([$applied, $compounds], \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param array<array-key, mixed> $props
     *
     * @return array<array-key, mixed>
     */
    public function resolve(array $props, ClassNameGenerator $generator): array
    {
        ['applied' => $applied, 'compounds' => $compounds] = $this->select($props);

        $css = $this->base;
        foreach ($applied as [$name, $lookup]) {
            $css = $generator->merge($css, $this->variants[$name][$lookup]);
        }

        $compound = [];
        foreach ($compounds as $index) {
            $compound = $generator->merge($compound, $this->compoundVariants[$index]['css']);
        }

        return $generator->merge($css, $compound);
    }

    /**
     * @return list<array<array-key, string|int|float|bool>>|null
     */
    public function combinations(int $limit): ?array
    {
        $axes = [];
        foreach ($this->defaultVariants as $name => $value) {
            $axes[$name] = array_map(self::naturalValue(...), array_keys($this->variants[$name]));
        }
        foreach ($this->variants as $name => $values) {
            if (!\array_key_exists($name, $this->defaultVariants)) {
                $axes[$name] = [null, ...array_map(self::naturalValue(...), array_keys($values))];
            }
        }

        $count = 1;
        foreach ($axes as $values) {
            $count *= \count($values);
            if ($count > $limit) {
                return null;
            }
        }

        $combinations = [[]];
        foreach ($axes as $name => $values) {
            $next = [];
            foreach ($combinations as $combination) {
                foreach ($values as $value) {
                    $next[] = $combination;
                    if (null !== $value) {
                        $next[array_key_last($next)][$name] = $value;
                    }
                }
            }
            $combinations = $next;
        }

        return $combinations;
    }

    /**
     * @param array<array-key, mixed> $props
     */
    public function undeclared(array $props): ?string
    {
        foreach ($props as $name => $value) {
            $name = (string) $name;
            if (null === $value) {
                continue;
            }
            if (!isset($this->variants[$name])) {
                $declared = implode('", "', array_keys($this->variants));

                return \sprintf('The cva() variant "%s" is not declared, declared variants are "%s".', $name, $declared);
            }
            if (null === $this->lookup($name, $value)) {
                $printed = \is_scalar($value) ? self::print($value) : get_debug_type($value);
                $declared = implode('", "', array_keys($this->variants[$name]));

                return \sprintf('The value "%s" of the cva() variant "%s" is not declared, declared values are "%s".', $printed, $name, $declared);
            }
        }

        return null;
    }

    /**
     * @param array<array-key, mixed> $props
     *
     * @return array{applied: list<array{string, string}>, compounds: list<int>}
     */
    private function select(array $props): array
    {
        $computed = $this->defaultVariants;
        foreach ($props as $name => $value) {
            // Panda's compact() only drops undefined; Twig has none, so null props fall back to the default
            if (null !== $value) {
                $computed[$name] = $value;
            }
        }

        $ordered = [];
        foreach (JsValue::objectKeys(array_keys($computed)) as $name) {
            $ordered[$name] = $computed[$name];
        }

        $applied = [];
        foreach ($ordered as $name => $value) {
            $name = (string) $name;
            $lookup = $this->lookup($name, $value);
            if (null !== $lookup) {
                $applied[] = [$name, $lookup];
            }
        }

        $compounds = [];
        foreach ($this->compoundVariants as $index => $compound) {
            if ($this->matches($compound['conditions'], $ordered)) {
                $compounds[] = $index;
            }
        }

        return ['applied' => $applied, 'compounds' => $compounds];
    }

    /**
     * @param array<array-key, list<mixed>> $conditions
     * @param array<array-key, mixed>       $computed
     */
    private function matches(array $conditions, array $computed): bool
    {
        foreach ($conditions as $name => $values) {
            if (!\array_key_exists($name, $computed)) {
                return false;
            }
            $matched = false;
            foreach ($values as $value) {
                if (self::jsStrictEquals($computed[$name], $value)) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                return false;
            }
        }

        return true;
    }

    private function lookup(string $name, mixed $value): ?string
    {
        if (!isset($this->variants[$name]) || !\is_scalar($value)) {
            return null;
        }

        try {
            $lookup = JsValue::toString($value);
        } catch (UnsupportedStyleException) {
            return null;
        }

        return \array_key_exists($lookup, $this->variants[$name]) ? $lookup : null;
    }

    private static function jsStrictEquals(mixed $a, mixed $b): bool
    {
        if ((\is_int($a) || \is_float($a)) && (\is_int($b) || \is_float($b))) {
            return $a == $b;
        }

        return \is_scalar($a) && $a === $b;
    }

    private static function naturalValue(string|int $name): string|int|float|bool
    {
        $name = (string) $name;
        if ('true' === $name || 'false' === $name) {
            return 'true' === $name;
        }

        $number = JsValue::toNumber($name);
        if (null === $number) {
            return $name;
        }

        try {
            return JsValue::toString($number) === $name ? $number : $name;
        } catch (UnsupportedStyleException) {
            return $name;
        }
    }

    /**
     * @param array<array-key, array<array-key, array<array-key, mixed>>> $variants
     */
    private static function assertDeclaredValue(array $variants, string $name, mixed $value, string $context): void
    {
        if (!isset($variants[$name])) {
            $message = \sprintf('The cva() %s "%s" is not a declared variant, declared variants are "%s".', $context, $name, implode('", "', array_keys($variants)));

            throw new InvalidStyleException($message);
        }
        if (!\is_scalar($value)) {
            $message = \sprintf('The cva() %s "%s" must be a declared value, got "%s".', $context, $name, get_debug_type($value));

            throw new InvalidStyleException($message);
        }

        try {
            $lookup = JsValue::toString($value);
        } catch (UnsupportedStyleException) {
            $lookup = null;
        }

        if (null === $lookup || !\array_key_exists($lookup, $variants[$name])) {
            $declared = implode('", "', array_keys($variants[$name]));
            $message = \sprintf('The value "%s" of the cva() %s "%s" is not declared, declared values are "%s".', self::print($value), $context, $name, $declared);

            throw new InvalidStyleException($message);
        }
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function styleHash(mixed $value, string $label): array
    {
        if (!\is_array($value) || ([] !== $value && array_is_list($value))) {
            $message = \sprintf('The cva() %s must be a style hash, got "%s".', $label, get_debug_type($value));

            throw new InvalidStyleException($message);
        }

        return $value;
    }

    private static function print(string|int|float|bool $value): string
    {
        try {
            return JsValue::toString($value);
        } catch (UnsupportedStyleException) {
            return var_export($value, true);
        }
    }
}
