<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Validation;

use Symfony\UX\Css\DependencyInjection\PandaConfigConverter;
use Symfony\UX\Css\Engine\Engine;
use Symfony\UX\Css\Exception\InvalidStyleException;

/**
 * Accepts what the TypeScript types Panda generates accept for the same config, and a few v1 rules on top.
 *
 * Port of `generateStyleProps()` and `generatePropTypes()` (packages/generator/src/artifacts/types): a value type
 * is a union of literals, open strings, numbers, booleans and `var(--*)`, widened by the escape hatch in strict modes.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class StyleValidator
{
    private const CSS_TYPES = __DIR__.'/../../resources/css-types.json';

    private const UNSAFE = 'cannot contain ";", "{", "}", "\\", quotes, "<" or a CSS comment';

    private const CSS_WIDE_KEYWORDS = [
        'inherit' => true,
        'initial' => true,
        'unset' => true,
        'revert' => true,
        'revert-layer' => true,
    ];

    // Panda's strictPropertyList (packages/generator/src/artifacts/types/style-props.ts)
    private const STRICT_PROPERTIES = [
        'alignContent',
        'alignItems',
        'alignSelf',
        'all',
        'animationComposition',
        'animationDirection',
        'animationFillMode',
        'appearance',
        'backfaceVisibility',
        'backgroundAttachment',
        'backgroundClip',
        'borderCollapse',
        'borderBlockEndStyle',
        'borderBlockStartStyle',
        'borderBlockStyle',
        'borderBottomStyle',
        'borderInlineEndStyle',
        'borderInlineStartStyle',
        'borderInlineStyle',
        'borderLeftStyle',
        'borderRightStyle',
        'borderTopStyle',
        'boxDecorationBreak',
        'boxSizing',
        'breakAfter',
        'breakBefore',
        'breakInside',
        'captionSide',
        'clear',
        'columnFill',
        'columnRuleStyle',
        'contentVisibility',
        'direction',
        'display',
        'emptyCells',
        'flexDirection',
        'flexWrap',
        'float',
        'fontKerning',
        'forcedColorAdjust',
        'isolation',
        'lineBreak',
        'mixBlendMode',
        'objectFit',
        'outlineStyle',
        'overflow',
        'overflowX',
        'overflowY',
        'overflowBlock',
        'overflowInline',
        'overflowWrap',
        'pointerEvents',
        'position',
        'resize',
        'scrollBehavior',
        'touchAction',
        'transformBox',
        'transformStyle',
        'userSelect',
        'visibility',
        'wordBreak',
        'writingMode',
    ];

    /**
     * @var array{properties: list<string>, types: array<string, array{keywords: list<string>, string: bool, number: bool}>}|null
     */
    private static ?array $cssTypes = null;

    /**
     * @var array<string, true>|null
     */
    private ?array $properties = null;

    /**
     * @var array<string, true>|null
     */
    private ?array $conditions = null;

    /**
     * @var array<string, array{type: array{literals: array<string, true>, string: bool, number: bool, boolean: bool, cssVars: bool}, hatch: bool, category: ?string, emptyCategory: bool}>
     */
    private array $valueTypes = [];

    public function __construct(
        private readonly Engine $engine,
        private readonly bool $strictTokens = true,
        private readonly bool $strictPropertyValues = true,
    ) {
    }

    /**
     * @param array<array-key, mixed> $styles
     *
     * @throws InvalidStyleException
     */
    public function validate(array $styles): void
    {
        foreach ($styles as $key => $value) {
            $key = (string) $key;

            if (self::isUnsafe($key)) {
                throw new InvalidStyleException(\sprintf('The key "%s" %s.', $key, self::UNSAFE));
            }

            if ('base' === $key) {
                throw new InvalidStyleException('"base" can only be used inside a conditional value, like { color: { base: \'red\', _hover: \'blue\' } }.');
            }
            if (str_starts_with($key, '--')) {
                $this->validateConditionalValue($key, $value, static fn (): bool => true);
                continue;
            }
            if (
                $this->isCondition($key)
                || str_starts_with($key, '&')
                || str_ends_with($key, '&')
                || str_starts_with($key, '@')
            ) {
                if (null === $value) {
                    continue;
                }
                if (!\is_array($value) || (array_is_list($value) && [] !== $value)) {
                    throw new InvalidStyleException(\sprintf('The "%s" condition must hold a hash of styles.', $key));
                }
                $this->validate($value);
                continue;
            }
            if (!isset($this->properties()[$key])) {
                if (str_starts_with($key, '_')) {
                    $suggestion = Suggestion::didYouMean($key, $this->conditionNames());

                    throw new InvalidStyleException(\sprintf('Unknown condition "%s".', $key).$suggestion);
                }

                $suggestion = Suggestion::didYouMean($key, $this->propertyAndBreakpointNames());

                throw new InvalidStyleException(\sprintf('Unknown property "%s".', $key).$suggestion);
            }

            $validateScalar = fn (mixed $scalar): bool => $this->validateScalar($key, $scalar);
            $this->validateConditionalValue($key, $value, $validateScalar);
        }
    }

    /**
     * @return list<string> the keys accepted as properties, sorted
     */
    public function propertyNames(): array
    {
        $names = array_map(strval(...), array_keys($this->properties()));
        sort($names, \SORT_STRING);

        return $names;
    }

    /**
     * @return list<string> the conditions and breakpoints accepted as keys, sorted
     */
    public function conditionKeys(): array
    {
        $names = $this->conditionNames();
        sort($names, \SORT_STRING);

        return $names;
    }

    /**
     * @return array{property: string, category: ?string, literals: list<string>, number: bool, boolean: bool}
     */
    public function valuesOf(string $key): array
    {
        $rule = $this->valueTypes[$key] ??= $this->valueType($key);
        $literals = array_map(strval(...), array_keys($rule['type']['literals']));
        sort($literals, \SORT_STRING);

        return [
            'property' => $this->engine->utilities()->resolveShorthand($key),
            'category' => $rule['category'],
            'literals' => $literals,
            'number' => $rule['type']['number'],
            'boolean' => $rule['type']['boolean'],
        ];
    }

    /**
     * @param \Closure(mixed): bool $validateScalar
     */
    private function validateConditionalValue(string $key, mixed $value, \Closure $validateScalar): void
    {
        if (null === $value) {
            return;
        }
        if (!\is_array($value)) {
            $this->assertSafe($key, $value);
            $validateScalar($value);

            return;
        }
        if (array_is_list($value)) {
            foreach ($value as $item) {
                if (null !== $item) {
                    $this->assertSafe($key, $item);
                    $validateScalar($item);
                }
            }

            return;
        }

        foreach ($value as $condition => $conditional) {
            $condition = (string) $condition;
            if ('base' !== $condition && !$this->isCondition($condition)) {
                $message = \sprintf('Unknown condition "%s" in the value of "%s".', $condition, $key);
                $suggestion = Suggestion::didYouMean($condition, ['base', ...$this->conditionNames()]);

                throw new InvalidStyleException($message.$suggestion);
            }
            $this->validateConditionalValue($key, $conditional, $validateScalar);
        }
    }

    private function assertSafe(string $key, mixed $value): void
    {
        if (!\is_string($value) && !\is_int($value) && !\is_float($value) && !\is_bool($value)) {
            $message = \sprintf(
                'The value of "%s" must be a string, a number or a boolean, "%s" given.',
                $key,
                get_debug_type($value),
            );

            throw new InvalidStyleException($message);
        }
        if (\is_string($value) && self::isUnsafe($value)) {
            $message = \sprintf('The value of "%s" %s, "%s" given.', $key, self::UNSAFE, $value);

            throw new InvalidStyleException($message);
        }
    }

    private function validateScalar(string $key, mixed $value): bool
    {
        if (\is_string($value) && isset(self::CSS_WIDE_KEYWORDS[$value])) {
            return true;
        }

        $rule = $this->valueTypes[$key] ??= $this->valueType($key);
        if (self::accepts($rule['type'], $rule['hatch'], $value)) {
            return true;
        }

        $shown = \is_string($value) ? $value : json_encode($value);
        if ($rule['emptyCategory']) {
            $message = \sprintf('Unknown %1$s token "%2$s": no %1$s token is declared.', $rule['category'], $shown);
            $hint = \sprintf(
                ' Add them under ux_css.tokens.%s, or write a raw value between brackets, like "[%s]".',
                $rule['category'],
                $shown,
            );

            throw new InvalidStyleException($message.$hint);
        }
        $suggestion = '';
        if (\is_string($value)) {
            $literals = array_map(strval(...), array_keys($rule['type']['literals']));
            $suggestion = Suggestion::didYouMean($value, $literals);
        }
        if (null !== $rule['category']) {
            if ('' === $suggestion && \is_string($value) && $rule['hatch']) {
                $suggestion = \sprintf(' Write a raw value between brackets, like "[%s]".', $value);
            }

            $message = \sprintf('Unknown %s token "%s".', $rule['category'], $shown);

            throw new InvalidStyleException($message.$suggestion);
        }

        throw new InvalidStyleException(\sprintf('Invalid value "%s" for "%s".', $shown, $key).$suggestion);
    }

    /**
     * @return array{type: array{literals: array<string, true>, string: bool, number: bool, boolean: bool, cssVars: bool}, hatch: bool, category: ?string}
     */
    private function valueType(string $key): array
    {
        $property = $this->engine->utilities()->resolveShorthand($key);
        $strictKey = \in_array($key, self::STRICT_PROPERTIES, true);
        $strictProperty = \in_array($property, self::STRICT_PROPERTIES, true);
        [$utilityType, $category, $boundCategory] = $this->utilityType($property);
        $cssType = \in_array($property, self::cssTypes()['properties'], true) ? self::cssType($property) : null;
        $cssVars = self::type(cssVars: true);

        if (null !== $utilityType) {
            $type = self::union($utilityType, $cssVars, !$strictKey && !$this->strictTokens ? $cssType : null);
        } else {
            $type = self::union($strictKey ? $cssVars : null, $cssType) ?? self::type(string: true, number: true);
        }

        if ($this->strictPropertyValues && $strictProperty) {
            $type = [...$type, 'string' => false, 'number' => false];

            return ['type' => $type, 'hatch' => true, 'category' => null, 'emptyCategory' => false];
        }
        if ($this->strictTokens && $this->hasNoToken($boundCategory)) {
            $type = [...$type, 'string' => false];

            return ['type' => $type, 'hatch' => true, 'category' => $boundCategory, 'emptyCategory' => true];
        }
        if ($this->strictTokens) {
            return ['type' => $type, 'hatch' => true, 'category' => $category, 'emptyCategory' => false];
        }

        $type = [...$type, 'string' => true];

        return ['type' => $type, 'hatch' => false, 'category' => null, 'emptyCategory' => false];
    }

    /**
     * Port of `Utility::assignPropertyType()`: the values a utility declares, if any.
     *
     * @return array{?array{literals: array<string, true>, string: bool, number: bool, boolean: bool, cssVars: bool}, ?string}
     */
    private function utilityType(string $property): array
    {
        $config = $this->engine->config()['utilities'][$property] ?? null;
        if ('colorPalette' === $property && [] !== ($names = $this->engine->tokens()->getColorPaletteNames())) {
            $config = ['values' => $names];
        }
        if (!\is_array($config)) {
            return [null, null, null];
        }

        $values = $config['values'] ?? null;
        $category = null;
        $literals = [];
        if (\is_string($values)) {
            $category = $values;
            $literals = array_map(strval(...), array_keys($this->engine->tokens()->getCategoryValues($values) ?? []));
        } elseif (\is_array($values) && isset($values['__function'])) {
            foreach ($values['probe'] ?? [] as $key => $value) {
                if (str_starts_with((string) $key, '__category:')) {
                    $keyCategory = substr($key, \strlen('__category:'));
                    $category ??= $keyCategory;
                    $categoryValues = $this->engine->tokens()->getCategoryValues($keyCategory) ?? [];
                    $literals = [...$literals, ...array_map(strval(...), array_keys($categoryValues))];
                } else {
                    $literals[] = (string) $key;
                }
            }
        } elseif (\is_array($values) && array_is_list($values)) {
            $literals = array_map(strval(...), $values);
        } elseif (\is_array($values) && isset($values['type'])) {
            return ['boolean' === $values['type'] ? self::type(boolean: true) : null, null, null];
        } elseif (\is_array($values)) {
            $literals = array_map(strval(...), array_keys($values));
        }

        $type = [] !== $literals ? self::type(literals: $literals) : null;
        if (!$this->strictTokens && isset($config['property'])) {
            $type = self::union($type, self::cssType($config['property']));
        }

        return [$type, [] !== $literals ? $category : null, $category];
    }

    /**
     * @param array{literals: array<string, true>, string: bool, number: bool, boolean: bool, cssVars: bool} $type
     */
    private static function accepts(array $type, bool $hatch, mixed $value): bool
    {
        if (\is_bool($value)) {
            return $type['boolean'];
        }
        if (\is_int($value) || \is_float($value)) {
            return $type['number'];
        }
        if (self::acceptsString($type, $value)) {
            return true;
        }
        if (!$hatch) {
            return false;
        }
        if (preg_match('/^\[.*\]$/s', $value)) {
            return true;
        }
        // WithColorOpacityModifier and WithImportant only apply when every member of the type is a string
        if ($type['boolean'] || $type['number']) {
            return false;
        }
        foreach (['!', '!important', ' !', ' !important'] as $important) {
            $withoutImportant = substr($value, 0, -\strlen($important));
            if (str_ends_with($value, $important) && self::acceptsString($type, $withoutImportant)) {
                return true;
            }
        }
        for ($position = strpos($value, '/'); false !== $position; $position = strpos($value, '/', $position + 1)) {
            if (self::acceptsString($type, substr($value, 0, $position))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array{literals: array<string, true>, string: bool, number: bool, boolean: bool, cssVars: bool} $type
     */
    private static function acceptsString(array $type, string $value): bool
    {
        return isset($type['literals'][$value])
            || $type['string']
            || ($type['cssVars'] && str_starts_with($value, 'var(--') && str_ends_with($value, ')'));
    }

    /**
     * @param list<string> $literals
     *
     * @return array{literals: array<string, true>, string: bool, number: bool, boolean: bool, cssVars: bool}
     */
    private static function type(
        array $literals = [],
        bool $string = false,
        bool $number = false,
        bool $boolean = false,
        bool $cssVars = false,
    ): array {
        return [
            'literals' => array_fill_keys($literals, true),
            'string' => $string,
            'number' => $number,
            'boolean' => $boolean,
            'cssVars' => $cssVars,
        ];
    }

    /**
     * @param array{literals: array<string, true>, string: bool, number: bool, boolean: bool, cssVars: bool}|null ...$types
     *
     * @return array{literals: array<string, true>, string: bool, number: bool, boolean: bool, cssVars: bool}|null
     */
    private static function union(?array ...$types): ?array
    {
        $union = null;
        foreach (array_filter($types) as $type) {
            $union = null === $union ? $type : [
                'literals' => $union['literals'] + $type['literals'],
                'string' => $union['string'] || $type['string'],
                'number' => $union['number'] || $type['number'],
                'boolean' => $union['boolean'] || $type['boolean'],
                'cssVars' => $union['cssVars'] || $type['cssVars'],
            ];
        }

        return $union;
    }

    /**
     * @return array{literals: array<string, true>, string: bool, number: bool, boolean: bool, cssVars: bool}
     */
    private static function cssType(string $property): array
    {
        $type = self::cssTypes()['types'][$property] ?? null;

        if (null === $type) {
            return self::type(string: true, number: true);
        }

        return self::type($type['keywords'], $type['string'], $type['number']);
    }

    /**
     * @return array{properties: list<string>, types: array<string, array{keywords: list<string>, string: bool, number: bool}>}
     */
    private static function cssTypes(): array
    {
        return self::$cssTypes ??= json_decode(file_get_contents(self::CSS_TYPES), true, flags: \JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, true>
     */
    private function properties(): array
    {
        if (null !== $this->properties) {
            return $this->properties;
        }

        $properties = array_fill_keys(self::cssTypes()['properties'], true);
        foreach ($this->engine->config()['utilities'] ?? [] as $property => $utility) {
            $properties[$property] = true;
            foreach ((array) ($utility['shorthand'] ?? []) as $shorthand) {
                $properties[$shorthand] = true;
            }
        }
        if ([] !== $this->engine->tokens()->getColorPaletteNames()) {
            $properties['colorPalette'] = true;
        }

        return $this->properties = $properties;
    }

    private function isCondition(string $key): bool
    {
        $this->conditions ??= array_fill_keys($this->engine->conditions()->getNames(), true);

        return isset($this->conditions[$key]);
    }

    private function hasNoToken(?string $category): bool
    {
        if (null === $category || !\in_array($category, PandaConfigConverter::CATEGORIES, true)) {
            return false;
        }

        return [] === ($this->engine->tokens()->getCategoryValues($category) ?? []);
    }

    private static function isUnsafe(string $text): bool
    {
        return false !== strpbrk($text, ';{}"\\\'<') || str_contains($text, '/*') || str_contains($text, '*/');
    }

    /**
     * @return list<string>
     */
    private function propertyAndBreakpointNames(): array
    {
        $names = array_map(strval(...), array_keys($this->properties()));
        foreach ($this->conditionNames() as $condition) {
            if (!str_starts_with($condition, '_')) {
                $names[] = $condition;
            }
        }

        return $names;
    }

    /**
     * @return list<string>
     */
    private function conditionNames(): array
    {
        $this->isCondition('');

        return array_map(strval(...), array_keys($this->conditions));
    }
}
