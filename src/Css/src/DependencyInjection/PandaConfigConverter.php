<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\UX\Css\Engine\PandaConfig;
use Symfony\UX\Css\Validation\Suggestion;

/**
 * Turns the `ux_css` config into the project part of a Panda config, and rejects what Panda would silently accept.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class PandaConfigConverter
{
    public const CATEGORIES = [
        'colors',
        'spacing',
        'sizes',
        'radii',
        'fontSizes',
        'fontWeights',
        'lineHeights',
        'fonts',
        'shadows',
        'zIndex',
        'durations',
        'easings',
    ];

    private const LIST_CATEGORIES = ['fonts', 'easings', 'shadows'];

    /**
     * Puts the tokens of the project on top of default ones: a project token, in any form, replaces the default
     * token or group at the same path.
     *
     * @param array<array-key, mixed> $defaults
     * @param array<array-key, mixed> $project
     *
     * @return array<array-key, mixed>
     */
    public static function mergeTokens(array $defaults, array $project): array
    {
        foreach ($project as $key => $value) {
            $default = $defaults[$key] ?? null;
            if (\is_array($default) && \is_array($value) && !self::isToken($default) && !self::isToken($value)) {
                $defaults[$key] = self::mergeTokens($default, $value);
            } else {
                $defaults[$key] = $value;
            }
        }

        return $defaults;
    }

    /**
     * @param array{tokens: array<string, mixed>, semantic_tokens: array<string, mixed>, conditions: array<string, string|list<string>>, breakpoints: array<string, string>} $config
     *
     * @return array{conditions: array<string, string|list<string>>, theme: array<string, mixed>}
     *
     * @throws InvalidConfigurationException
     */
    public static function convert(array $config): array
    {
        $preset = PandaConfig::create([]);

        foreach ($config['conditions'] as $name => $condition) {
            foreach ((array) $condition as $part) {
                if (!\is_string($part) || (!str_contains($part, '&') && !str_starts_with($part, '@'))) {
                    $message = \sprintf(
                        'The "%s" condition must contain "&" or start with "@", %s given.',
                        $name,
                        json_encode($part, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE),
                    );

                    throw new InvalidConfigurationException($message);
                }
            }
        }

        $reserved = [];
        foreach ($preset['utilities'] as $property => $utility) {
            $reserved[$property] = true;
            foreach ((array) ($utility['shorthand'] ?? []) as $shorthand) {
                $reserved[$shorthand] = true;
            }
        }
        foreach (array_keys($config['breakpoints']) as $name) {
            if (isset($reserved[$name])) {
                $message = \sprintf('The "%s" breakpoint has the same name as a CSS property or shorthand.', $name);

                throw new InvalidConfigurationException($message);
            }
        }

        $breakpoints = [] !== $config['breakpoints'] ? $config['breakpoints'] : $preset['theme']['breakpoints'];
        $conditionKeys = ['base', ...array_map(strval(...), array_keys($breakpoints))];
        foreach (array_keys($config['conditions'] + $preset['conditions']) as $name) {
            $conditionKeys[] = '_'.$name;
        }

        $values = [];
        foreach (array_keys($breakpoints) as $name) {
            $values['breakpoints.'.$name] = null;
            $values['sizes.breakpoint-'.$name] = null;
        }
        $tokens = self::convertCategories($config['tokens'], false, $values, $conditionKeys);
        $semanticTokens = self::convertCategories($config['semantic_tokens'], true, $values, $conditionKeys);

        $references = [];
        foreach ($values as $name => $value) {
            foreach (self::references($value) as $reference) {
                if (!\array_key_exists($reference, $values)) {
                    $message = \sprintf('The "%s" token references the unknown token "%s".', $name, $reference);
                    $suggestion = Suggestion::didYouMean($reference, array_map(strval(...), array_keys($values)));

                    throw new InvalidConfigurationException($message.$suggestion);
                }
                $references[$name][] = $reference;
            }
        }
        self::assertNoCycle($references);

        return [
            'conditions' => $config['conditions'],
            'theme' => array_filter([
                'tokens' => $tokens,
                'semanticTokens' => $semanticTokens,
                'breakpoints' => $config['breakpoints'],
            ]),
        ];
    }

    /**
     * @param array<string, mixed> $categories
     * @param array<string, mixed> $values        every token value, indexed by name
     * @param list<string>         $conditionKeys
     *
     * @return array<string, mixed>
     */
    private static function convertCategories(
        array $categories,
        bool $semantic,
        array &$values,
        array $conditionKeys,
    ): array {
        $converted = [];
        foreach ($categories as $category => $tokens) {
            if (!\in_array($category, self::CATEGORIES, true)) {
                $message = \sprintf('Unknown token category "%s".', $category);
                $suggestion = Suggestion::didYouMean($category, self::CATEGORIES);

                throw new InvalidConfigurationException($message.$suggestion);
            }

            $converted[$category] = self::convertToken($tokens, [$category], $semantic, $values, $conditionKeys);
        }

        return $converted;
    }

    /**
     * @param list<string>         $path
     * @param array<string, mixed> $values
     * @param list<string>         $conditionKeys
     */
    private static function convertToken(
        mixed $token,
        array $path,
        bool $semantic,
        array &$values,
        array $conditionKeys,
    ): array {
        if ('DEFAULT' === $path[0]) {
            $namePath = $path;
        } else {
            $namePath = array_filter($path, static fn (string $part): bool => 'DEFAULT' !== $part);
        }
        $name = implode('.', $namePath);

        // Panda's form, { value: ..., description: ... }
        if (\is_array($token) && \array_key_exists('value', $token)) {
            return self::convertValue($token['value'], $name, $path, $semantic, $values, $conditionKeys);
        }

        $isConditional = $semantic && \is_array($token) && \array_key_exists('base', $token);
        $isListValue = \is_array($token)
            && array_is_list($token)
            && \in_array($path[0], self::LIST_CATEGORIES, true)
            && 1 < \count($path);
        if (\is_array($token) && !$isConditional && !$isListValue) {
            $group = [];
            foreach ($token as $key => $child) {
                $childPath = [...$path, (string) $key];
                $group[$key] = self::convertToken($child, $childPath, $semantic, $values, $conditionKeys);
            }

            return $group;
        }

        return self::convertValue($token, $name, $path, $semantic, $values, $conditionKeys);
    }

    /**
     * @param list<string>         $path
     * @param array<string, mixed> $values
     * @param list<string>         $conditionKeys
     *
     * @return array{value: mixed}
     */
    private static function convertValue(
        mixed $value,
        string $name,
        array $path,
        bool $semantic,
        array &$values,
        array $conditionKeys,
    ): array {
        if ($semantic && \is_array($value) && \array_key_exists('base', $value)) {
            self::assertConditions($value, $name, $conditionKeys);
            $values[$name] = $value;

            return ['value' => $value];
        }

        $isList = \is_array($value) && array_is_list($value) && \in_array($path[0], self::LIST_CATEGORIES, true);
        if (!$isList && !\is_string($value) && !\is_int($value) && !\is_float($value)) {
            $message = \sprintf(
                'The value of the "%s" token must be a string or a number, %s given.',
                $name,
                get_debug_type($value),
            );

            throw new InvalidConfigurationException($message);
        }
        $values[$name] = $value;

        return ['value' => $value];
    }

    /**
     * @param array<array-key, mixed> $token
     */
    private static function isToken(array $token): bool
    {
        return array_is_list($token) || \array_key_exists('value', $token);
    }

    /**
     * @param array<array-key, mixed> $conditions
     * @param list<string>            $conditionKeys
     */
    private static function assertConditions(array $conditions, string $name, array $conditionKeys): void
    {
        foreach ($conditions as $key => $value) {
            if (!\in_array((string) $key, $conditionKeys, true)) {
                $message = \sprintf('The "%s" token uses the unknown condition "%s".', $name, $key);
                $suggestion = Suggestion::didYouMean((string) $key, $conditionKeys);

                throw new InvalidConfigurationException($message.$suggestion);
            }
            if (\is_array($value)) {
                self::assertConditions($value, $name, $conditionKeys);
            }
        }
    }

    /**
     * @return list<string> the tokens named in `{colors.red.500}` and `token(colors.red.500)`, without their color mix opacity
     */
    private static function references(mixed $value): array
    {
        if (\is_array($value)) {
            return array_merge(...array_map(self::references(...), array_values($value)));
        }
        if (!\is_string($value)) {
            return [];
        }

        preg_match_all('/\{([^}]*)\}|token\(([^,)]+)/', $value, $matches, \PREG_SET_ORDER);

        return array_map(static function (array $match): string {
            $reference = '' !== $match[1] ? $match[1] : $match[2];

            return explode('/', trim($reference))[0];
        }, $matches);
    }

    /**
     * @param array<string, list<string>> $references
     */
    private static function assertNoCycle(array $references): void
    {
        $done = [];
        $visit = static function (string $name, array $stack) use (&$visit, &$done, $references): void {
            if (isset($done[$name])) {
                return;
            }
            $position = array_search($name, $stack, true);
            if (false !== $position) {
                $cycle = implode(' -> ', [...\array_slice($stack, $position), $name]);

                throw new InvalidConfigurationException(\sprintf('Circular token reference: %s.', $cycle));
            }
            foreach ($references[$name] ?? [] as $reference) {
                $visit($reference, [...$stack, $name]);
            }
            $done[$name] = true;
        };

        foreach (array_keys($references) as $name) {
            $visit((string) $name, []);
        }
    }
}
