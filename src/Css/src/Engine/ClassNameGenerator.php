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
 * Port of the `css()` of Panda's generated runtime (packages/shared/src/classname.ts): class names only, from precomputed tables.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class ClassNameGenerator
{
    private const IMPORTANT = '/'.JsValue::WHITESPACE.'*!(important)?/iu';

    // "!" and the first byte of every JavaScript whitespace character: values without them need no cleanup
    private const CLEANUP_BYTES = "!\t\n\x0B\x0C\r \xC2\xE1\xE2\xE3\xEF";

    /**
     * @var array<string, true>
     */
    private readonly array $conditionNames;

    /**
     * @var array<string, string>
     */
    private array $propertyPrefixes = [];

    /**
     * @param array<string, string> $classNames     class name of each utility, indexed by property
     * @param array<string, string> $shorthands     property of each shorthand, indexed by shorthand
     * @param list<string>          $conditionNames every condition key: `_hover`, breakpoints and their ranges, containers, themes
     * @param list<string>          $breakpointKeys `base` then the breakpoints, smallest first, for responsive arrays
     */
    public function __construct(
        private readonly array $classNames,
        private readonly array $shorthands,
        array $conditionNames,
        private readonly array $breakpointKeys,
        private readonly string $separator = '_',
        private readonly string $prefix = '',
    ) {
        $this->conditionNames = array_fill_keys($conditionNames, true);
    }

    /**
     * @param array<string, mixed> $config a Panda config
     */
    public static function fromPandaConfig(array $config): self
    {
        if ($config['hash'] ?? false) {
            throw new UnsupportedStyleException('Hashed class names are not supported.');
        }

        $classNames = [];
        $shorthands = [];
        foreach ($config['utilities'] ?? [] as $property => $utility) {
            if ('' !== ($utility['className'] ?? '')) {
                $classNames[$property] = $utility['className'];
            }
            if (false !== ($config['shorthands'] ?? true)) {
                foreach ((array) ($utility['shorthand'] ?? []) as $shorthand) {
                    $shorthands[$shorthand] = $property;
                }
            }
        }

        $prefix = $config['prefix'] ?? '';
        if (\is_array($prefix)) {
            $prefix = $prefix['className'] ?? '';
        }

        $breakpoints = new Breakpoints($config['theme']['breakpoints'] ?? []);
        $conditions = new Conditions(
            $config['conditions'] ?? [],
            $breakpoints,
            $config['theme']['containerSizes'] ?? [],
            $config['theme']['containerNames'] ?? [],
            $config['themes'] ?? [],
        );

        return new self(
            $classNames,
            $shorthands,
            $conditions->getNames(),
            $breakpoints->keys,
            $config['separator'] ?? '_',
            (string) $prefix,
        );
    }

    /**
     * @return array{classNames: array<string, string>, shorthands: array<string, string>, conditionNames: list<string>, breakpointKeys: list<string>, separator: string, prefix: string}
     */
    public function toArray(): array
    {
        return [
            'classNames' => $this->classNames,
            'shorthands' => $this->shorthands,
            'conditionNames' => array_map(strval(...), array_keys($this->conditionNames)),
            'breakpointKeys' => $this->breakpointKeys,
            'separator' => $this->separator,
            'prefix' => $this->prefix,
        ];
    }

    /**
     * @param array<array-key, mixed> $styles
     */
    public function generate(array $styles): string
    {
        if (\array_key_exists('base', $styles)) {
            $base = $styles['base'];
            unset($styles['base']);
            foreach (\is_array($base) ? $base : [] as $key => $value) {
                $styles[$key] = $value;
            }
        }

        $classNames = [];
        $this->walk($this->normalize($styles), [], $classNames);

        return implode(' ', array_keys($classNames));
    }

    /**
     * @param array<array-key, mixed> $styles
     *
     * @return array<string, mixed>
     */
    private function normalize(array $styles): array
    {
        $normalized = [];
        foreach ($styles as $key => $value) {
            $key = (string) $key;
            $key = $this->shorthands[$key] ?? $key;
            if (\is_array($value)) {
                $value = array_is_list($value) ? $this->toResponsive($value) : $this->normalize($value);
            }
            if (null !== $value) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @param list<mixed> $values
     *
     * @return array<string, mixed>
     */
    private function toResponsive(array $values): array
    {
        $responsive = [];
        foreach ($values as $index => $value) {
            if (null !== $value) {
                $responsive[$this->breakpointKeys[$index] ?? 'undefined'] = $value;
            }
        }

        return $responsive;
    }

    /**
     * @param array<string, mixed> $styles
     * @param list<string>         $path
     * @param array<string, true>  $classNames
     */
    private function walk(array $styles, array $path, array &$classNames): void
    {
        foreach ($styles as $key => $value) {
            $keyPath = [...$path, (string) $key];
            if (\is_array($value)) {
                $this->walk($value, $keyPath, $classNames);
            } elseif (null !== $value) {
                $classNames[$this->toClassName($keyPath, $value)] = true;
            }
        }
    }

    /**
     * @param list<string> $path
     */
    private function toClassName(array $path, mixed $value): string
    {
        if (!\is_string($value) && !\is_int($value) && !\is_float($value) && !\is_bool($value)) {
            $message = \sprintf(
                'The value of "%s" must be a string, a number or a boolean, "%s" given.',
                implode('.', $path),
                get_debug_type($value),
            );

            throw new UnsupportedStyleException($message);
        }

        $important = false;
        if (\is_string($value) && false !== strpbrk($value, self::CLEANUP_BYTES)) {
            $important = str_contains($value, '!');
            $value = JsValue::trim(preg_replace(self::IMPORTANT, '', JsValue::collapseWhitespace($value), 1));
        }

        $keys = [];
        $conditions = [];
        foreach ($path as $key) {
            if (
                'base' === $key
                || isset($this->conditionNames[$key])
                || str_starts_with($key, '@')
                || str_contains($key, '&')
            ) {
                $conditions[] = $key;
            } else {
                $keys[] = $key;
            }
        }
        $property = [] !== $keys ? array_shift($keys) : array_shift($conditions);

        $className = $this->propertyPrefixes[$property] ??= $this->propertyPrefix($property);
        $className .= \is_string($value) ? str_replace(' ', '_', $value) : JsValue::toString($value);

        $prefix = '';
        foreach ([...$keys, ...$conditions] as $condition) {
            if ('base' !== $condition) {
                $prefix .= $this->finalize($condition).':';
            }
        }

        return $prefix.$className.($important ? '!' : '');
    }

    private function propertyPrefix(string $property): string
    {
        $utility = $this->shorthands[$property] ?? $property;
        $prefix = '' !== $this->prefix ? $this->prefix.'-' : '';

        return $prefix.($this->classNames[$utility] ?? PropertyName::toCss($utility)).$this->separator;
    }

    private function finalize(string $condition): string
    {
        if (isset($this->conditionNames[$condition])) {
            return str_starts_with($condition, '_') ? substr($condition, 1) : $condition;
        }
        if (str_contains($condition, '&') || str_contains($condition, '@')) {
            return '['.str_replace(' ', '_', JsValue::trim($condition)).']';
        }

        return $condition;
    }
}
