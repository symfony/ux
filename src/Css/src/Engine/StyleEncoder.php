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
 * Port of `StyleEncoder::hashStyleObject()` (packages/core/src/style-encoder.ts).
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class StyleEncoder
{
    public function __construct(
        private readonly Utilities $utilities,
        private readonly Conditions $conditions,
    ) {
    }

    /**
     * @param array<array-key, mixed> $styles
     *
     * @return list<StyleEntry>
     *
     * @throws UnsupportedStyleException when a value is neither a scalar nor a style object
     */
    public function encode(array $styles): array
    {
        $entries = [];
        $property = '';
        $previousProperty = '';
        $this->traverse($this->normalize($styles), [], $entries, $property, $previousProperty);

        return array_values($entries);
    }

    /**
     * @param array<string, mixed>      $styles
     * @param list<string>              $path
     * @param array<string, StyleEntry> $entries
     */
    private function traverse(
        array $styles,
        array $path,
        array &$entries,
        string &$property,
        string &$previousProperty,
    ): void {
        foreach ($styles as $key => $value) {
            $key = (string) $key;
            $keyPath = [...$path, $key];

            if (\is_string($value) && preg_match('#^https?://#', $value)) {
                continue;
            }

            $property = $key;
            if ($this->conditions->isCondition($key)) {
                if (\is_array($value)) {
                    $this->traverse($value, $keyPath, $entries, $property, $previousProperty);
                    continue;
                }
                $property = $previousProperty;
            } elseif (\is_array($value)) {
                $previousProperty = $property;
                $this->traverse($value, $keyPath, $entries, $property, $previousProperty);
                continue;
            }

            if (!\is_string($value) && !\is_int($value) && !\is_float($value) && !\is_bool($value)) {
                $message = \sprintf(
                    'The value of "%s" must be a string, a number or a boolean, "%s" given.',
                    implode('.', $keyPath),
                    get_debug_type($value),
                );

                throw new UnsupportedStyleException($message);
            }

            $entry = new StyleEntry($property, JsValue::toString($value), $this->resolveConditions($keyPath));
            $entries[$entry->hash()] ??= $entry;
            $previousProperty = $property;
        }
    }

    /**
     * @param list<string> $path
     *
     * @return list<string>
     */
    private function resolveConditions(array $path): array
    {
        $isCondition = fn (string $part): bool => 'base' !== $part && $this->conditions->isCondition($part);

        return array_values(array_filter($path, $isCondition));
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
            $key = $this->utilities->resolveShorthand((string) $key);
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
                $responsive[$this->conditions->breakpoints->keys[$index] ?? 'undefined'] = $value;
            }
        }

        return $responsive;
    }
}
