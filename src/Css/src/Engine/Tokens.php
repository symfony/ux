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

use Symfony\UX\Css\Exception\InvalidArgumentException;
use Symfony\UX\Css\Exception\UnsupportedStyleException;

/**
 * Port of Panda's `TokenDictionary` and of its computed view (packages/token-dictionary/src/dictionary.ts).
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class Tokens
{
    private const LENGTH_UNIT = '/^[+-]?[0-9]*.?[0-9]+(?:[eE][+-]?[0-9]+)?(?:cm|mm|Q|in|pc|pt|px|em|ex|ch|rem|lh|rlh|vw|vh|vmin|vmax|vb|vi|svw|svh|lvw|lvh|dvw|dvh|cqw|cqh|cqi|cqb|cqmin|cqmax|%)$/';

    /**
     * @var list<Token>
     */
    private array $allTokens = [];

    /**
     * @var array<string, Token>
     */
    private array $byName = [];

    /**
     * @var array<string, string>
     */
    private array $values = [];

    /**
     * @var array<string, array<string, string>>
     */
    private array $valuesByCategory = [];

    /**
     * @var array<string, array<string, string>>
     */
    private array $colorPalettes = [];

    /**
     * @var array<string, array<string, mixed>> CSS variables and their value, indexed by condition
     */
    private array $vars = [];

    /**
     * @param array<string, mixed>                                                  $tokens         the `theme.tokens` section of a Panda config
     * @param array<string, mixed>                                                  $semanticTokens the `theme.semanticTokens` section of a Panda config
     * @param array<string, string>                                                 $breakpoints
     * @param array{enabled?: bool, include?: list<string>, exclude?: list<string>} $colorPalette   the `theme.colorPalette` section of a Panda config
     */
    public function __construct(
        array $tokens = [],
        array $semanticTokens = [],
        array $breakpoints = [],
        private readonly string $prefix = '',
        bool $hash = false,
        array $colorPalette = [],
    ) {
        if ($hash) {
            throw new UnsupportedStyleException('Hashed token variables are not supported yet.');
        }
        if (isset($colorPalette['include']) || isset($colorPalette['exclude'])) {
            throw new UnsupportedStyleException('Filtering color palettes is not supported yet.');
        }
        $colorPalettes = $colorPalette['enabled'] ?? true;

        $this->registerTokens($tokens, $semanticTokens, $breakpoints);
        $this->addNegativeTokens();
        foreach ($this->allTokens as $token) {
            $this->transformValue($token, self::transformCategoryValue(...));
            $this->addCssVariable($token);
        }
        foreach ($this->allTokens as $token) {
            if (
                'colors' === ($token->extensions['category'] ?? null)
                && \is_string($token->value)
                && str_contains($token->value, '/')
            ) {
                $this->transformValue($token, $this->mixColorReferences(...));
            }
        }
        if ($colorPalettes) {
            foreach ($this->allTokens as $token) {
                $this->addColorPalette($token);
            }
        }
        $this->addConditionalTokens();
        $this->addReferences();
        foreach ($this->allTokens as $token) {
            $this->expandTokenReferences($token);
        }
        if ($colorPalettes) {
            $this->addVirtualPalette();
        }
        $tokensWithValue = array_filter($this->allTokens, static fn (Token $token): bool => '' !== $token->value);
        $this->allTokens = array_values($tokensWithValue);
        foreach ($this->allTokens as $token) {
            $this->addConditionalCssVariables($token);
        }

        foreach ($this->allTokens as $token) {
            $this->processColorPalette($token);
            $this->processValue($token);
            $this->processVars($token);
        }
    }

    public function getVar(string $name): ?string
    {
        return $this->values[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return isset($this->byName[$name]);
    }

    /**
     * @return array<string, array<string, mixed>> CSS variables and their value, indexed by condition (`base`, `_dark`, `md`, `_dark:md`…)
     */
    public function getVars(): array
    {
        return $this->vars;
    }

    public function getValue(string $name): mixed
    {
        return ($this->byName[$name] ?? null)?->value;
    }

    /**
     * @return array<string, string>|null
     */
    public function getCategoryValues(string $category): ?array
    {
        return $this->valuesByCategory[$category] ?? null;
    }

    /**
     * @return array<string, string>|null the CSS variables a `colorPalette` style sets, mapped to the palette colors
     */
    public function getColorPalette(string $name): ?array
    {
        return $this->colorPalettes[$name] ?? null;
    }

    /**
     * @return list<string>
     */
    public function getColorPaletteNames(): array
    {
        return array_map(strval(...), array_keys($this->colorPalettes));
    }

    /**
     * Port of `colorMix()` (packages/core/src/color-mix.ts): `red.300/50` mixes the `colors.red.300` token with transparent.
     *
     * @return array{invalid: bool, color?: string, value: string}
     */
    public function colorMix(string $value): array
    {
        return $this->mix($value, fn (string $color): ?string => $this->getVar('colors.'.$color));
    }

    /**
     * Port of `TokenDictionary::resolveReference()`: `{sizes.sm}` and `token(sizes.sm)` become the raw token value.
     */
    public function resolveReferences(string $value): string
    {
        return $this->expandCurlyReferences($value, function (string $name): ?string {
            $value = $this->getValue($name);

            return \is_scalar($value) ? JsValue::toString($value) : null;
        });
    }

    /**
     * Port of `TokenDictionary::expandReferenceInValue()`: `{colors.red.300}` and `token(colors.red.300, blue)` become CSS variables.
     *
     * @throws InvalidArgumentException when a color mix is invalid
     */
    public function expandReferences(string $value): string
    {
        return self::expandTokenFunctions($value, function (string $path): ?string {
            if ('' === $path) {
                return null;
            }

            if (str_contains($path, '/')) {
                $mix = $this->mix($path, $this->getVar(...));
                if ($mix['invalid']) {
                    throw new InvalidArgumentException(\sprintf('Invalid color mix at %s: %s', $path, $mix['value']));
                }

                return $mix['value'];
            }

            $resolved = $this->getVar($path);
            if (null !== $resolved && '' !== $resolved) {
                return $resolved;
            }

            return preg_match('/\w+\.\w+/', $path) ? CssEscaper::escape($path) : $path;
        });
    }

    /**
     * Port of `isCompositeTokenValue()` (packages/token-dictionary/src/is-composite.ts).
     *
     * @internal
     */
    public static function isComposite(mixed $value): bool
    {
        if (!\is_array($value)) {
            return false;
        }

        return array_is_list($value)
            || self::isCompositeGradient($value)
            || self::isCompositeShadow($value)
            || self::isCompositeBorder($value)
            || self::isCompositeAsset($value);
    }

    /**
     * @param array<string, mixed>  $tokens
     * @param array<string, mixed>  $semanticTokens
     * @param array<string, string> $breakpoints
     */
    private function registerTokens(array $tokens, array $semanticTokens, array $breakpoints): void
    {
        $tokens['breakpoints'] = array_map(static fn (string $value): array => ['value' => $value], $breakpoints);
        $sizes = $tokens['sizes'] ?? [];
        foreach ($breakpoints as $name => $value) {
            $sizes['breakpoint-'.$name] = ['value' => $value];
        }
        $tokens['sizes'] = $sizes;

        self::walkTokens($tokens, [], function (mixed $token, array $path): void {
            [$path, $isDefault] = self::filterDefault($path);
            $token = self::assertToken($token);
            $extensions = [
                ...($token['extensions'] ?? []),
                'category' => $path[0] ?? null,
                'prop' => implode('.', \array_slice($path, 1)),
            ];
            if ($isDefault) {
                $extensions['isDefault'] = true;
            }

            $this->registerToken(new Token(implode('.', $path), $token['value'], $path, $extensions));
        });

        self::walkTokens($semanticTokens, [], function (mixed $token, array $path): void {
            [$path, $isDefault] = self::filterDefault($path);
            $token = self::assertToken($token);
            $conditions = $token['value'];
            if (\is_string($conditions) || self::isComposite($conditions)) {
                $conditions = ['base' => $conditions];
            }
            $base = \is_array($conditions) ? ($conditions['base'] ?? null) : null;
            $extensions = [
                ...($token['extensions'] ?? []),
                'category' => $path[0] ?? null,
                'conditions' => $conditions,
                'rawValue' => $conditions,
                'prop' => implode('.', \array_slice($path, 1)),
            ];
            if ($isDefault) {
                $extensions['isDefault'] = true;
            }

            $value = JsValue::isTruthy($base) ? $base : '';
            $this->registerToken(new Token(implode('.', $path), $value, $path, $extensions));
        });
    }

    private function registerToken(Token $token): void
    {
        $this->allTokens[] = $token;
        $this->byName[$token->name] = $token;
    }

    private function addNegativeTokens(): void
    {
        foreach ($this->allTokens as $token) {
            if ('spacing' !== ($token->extensions['category'] ?? null) || '0rem' === $token->value) {
                continue;
            }

            $negative = $token->copy();
            $negative->extensions = [
                ...$negative->extensions,
                'isNegative' => true,
                'prop' => '-'.$token->extensions['prop'],
                'originalPath' => $token->path,
            ];
            $negative->value = 'calc('.str_replace('calc', '', $this->cssVar($token->path)['ref'].' * -1').')';
            $last = array_key_last($negative->path);
            if (null !== $last) {
                $negative->path[$last] = '-'.$negative->path[$last];
            }
            $negative->name = implode('.', $negative->path);

            $this->registerToken($negative);
        }
    }

    /**
     * Port of `TokenDictionary::execTransformOnToken()` for value transforms.
     *
     * @param callable(mixed, Token): mixed $transform
     */
    private function transformValue(Token $token, callable $transform): void
    {
        $token->value = $transform($token->value, $token);
        if ($token->isComposite()) {
            $token->originalValue = $token->value;
        }

        if ($token->isConditional()) {
            $token->extensions['conditions'] = self::mapConditions(
                $token->extensions['conditions'],
                static fn (mixed $value): mixed => $transform($value, $token),
            );
        }
    }

    private static function transformCategoryValue(mixed $value, Token $token): mixed
    {
        $isList = \is_array($value) && array_is_list($value);

        return match ($token->extensions['category'] ?? null) {
            'shadows' => self::transformShadow($value),
            'gradients' => self::transformGradient($value),
            'fonts' => $isList ? self::join($value, ', ') : $value,
            'easings' => $isList ? 'cubic-bezier('.self::join($value, ', ').')' : $value,
            'borders' => self::transformBorder($value),
            'assets' => self::transformAsset($value),
            default => $value,
        };
    }

    private static function transformShadow(mixed $value): mixed
    {
        if (\is_array($value) && array_is_list($value)) {
            return self::join(array_map(self::transformShadow(...), $value), ', ');
        }
        if (!\is_array($value) || !self::isCompositeShadow($value)) {
            return $value;
        }

        $px = static fn (string|int|float $value): string => \is_string($value)
            ? $value
            : JsValue::toString($value).'px';
        $parts = [
            ($value['inset'] ?? false) ? 'inset ' : '',
            $px($value['offsetX']),
            $px($value['offsetY']),
            $px($value['blur']),
            $px($value['spread']),
            $value['color'],
        ];

        return implode(' ', array_filter($parts, static fn (string $part): bool => '' !== $part));
    }

    private static function transformGradient(mixed $value): mixed
    {
        if (!\is_array($value) || !self::isCompositeGradient($value)) {
            return $value;
        }

        $formatStop = static fn (string|array $stop): string => \is_string($stop)
            ? $stop
            : $stop['color'].' '.JsValue::toString($stop['position']).'px';
        $stops = array_map($formatStop, $value['stops']);

        return $value['type'].'-gradient('.$value['placement'].', '.implode(', ', $stops).')';
    }

    private static function transformBorder(mixed $value): mixed
    {
        if (!\is_array($value) || !self::isCompositeBorder($value)) {
            return $value;
        }

        $width = JsValue::toString($value['width']);
        if (
            !\is_string($value['width'])
            || (!preg_match(self::LENGTH_UNIT, $width) && !preg_match('/\{[^}]*\}/', $width))
        ) {
            $width .= 'px';
        }

        return $width.' '.$value['style'].' '.$value['color'];
    }

    private static function transformAsset(mixed $value): string
    {
        if (\is_string($value)) {
            return $value;
        }
        if (\is_array($value) && 'url' === ($value['type'] ?? null) && \is_string($value['value'] ?? null)) {
            return 'url("'.$value['value'].'")';
        }
        if (\is_array($value) && 'svg' === ($value['type'] ?? null) && \is_string($value['value'] ?? null)) {
            return 'url("'.SvgDataUri::encode($value['value']).'")';
        }

        throw new InvalidArgumentException(\sprintf('Invalid asset token: %s', json_encode($value)));
    }

    private function addCssVariable(Token $token): void
    {
        $path = ($token->extensions['isNegative'] ?? false) ? $token->extensions['originalPath'] : $token->path;
        $variable = $this->cssVar(array_values(array_filter($path, static fn (string $part): bool => '' !== $part)));
        $token->extensions['var'] = $variable['var'];
        $token->extensions['varRef'] = $variable['ref'];
    }

    private function mixColorReferences(mixed $value): mixed
    {
        if (!\is_string($value) || !str_contains($value, '/')) {
            return $value;
        }

        return $this->expandCurlyReferences($value, function (string $path): string {
            $mix = $this->mix(
                $path,
                fn (string $name): ?string => ($this->byName[$name] ?? null)?->extensions['varRef'] ?? null,
            );
            if ($mix['invalid']) {
                throw new InvalidArgumentException(\sprintf('Invalid color mix at %s: %s', $path, $mix['value']));
            }

            return $mix['value'];
        });
    }

    private function addColorPalette(Token $token): void
    {
        if ('colors' !== ($token->extensions['category'] ?? null) || ($token->extensions['isVirtual'] ?? false)) {
            return;
        }

        $colorPath = \array_slice($token->path, 1, -1);
        if ([] === $colorPath) {
            $colorPath = \array_slice($token->path, 1);
            if ([] === $colorPath) {
                return;
            }
        }

        $roots = [];
        foreach ($colorPath as $i => $segment) {
            $roots[] = \array_slice($colorPath, 0, $i + 1);
        }

        $start = array_search($colorPath[0], $token->path, true) + 1;
        $remaining = \array_slice($token->path, $start);
        $keys = [];
        foreach ($remaining as $i => $segment) {
            $keys[] = \array_slice($remaining, $i);
        }

        $token->extensions['colorPalette'] = implode('.', $colorPath);
        $token->extensions['colorPaletteRoots'] = $roots;
        $token->extensions['colorPaletteTokenKeys'] = [] === $keys ? [['']] : $keys;
    }

    private function addConditionalTokens(): void
    {
        foreach ($this->allTokens as $token) {
            if (!$token->isConditional()) {
                continue;
            }

            $conditions = $token->extensions['conditions'];
            self::walkLeaves($conditions, [], function (mixed $value, array $path) use ($token): void {
                $path = array_values(array_filter($path, static fn (string $part): bool => 'base' !== $part));
                if ([] === $path) {
                    return;
                }

                $conditional = $token->copy();
                $conditional->value = $value;
                $conditional->extensions['condition'] = implode(':', $path);

                $this->registerToken($conditional);
            });
        }
    }

    private function addReferences(): void
    {
        foreach ($this->allTokens as $token) {
            if (!\is_string($token->value)) {
                continue;
            }

            $references = [];
            foreach (self::getReferences($token->value) as $reference) {
                if (isset($this->byName[$reference])) {
                    $references[$reference] = $this->byName[$reference];
                }
            }
            if ([] !== $references) {
                $token->extensions['references'] = $references;
            }
        }
    }

    /**
     * @param array<string, true> $visiting
     */
    private function expandTokenReferences(Token $token, array $visiting = []): mixed
    {
        if (!isset($token->extensions['references'])) {
            return $token->extensions['varRef'] ?? $token->value;
        }
        if (isset($visiting[$token->name])) {
            throw new InvalidArgumentException(\sprintf('The "%s" token references itself.', $token->name));
        }
        $visiting[$token->name] = true;

        foreach ($token->extensions['references'] as $name => $reference) {
            if (!$reference->isConditional()) {
                $expanded = JsValue::toString($this->expandTokenReferences($reference, $visiting));
                $token->value = str_replace('{'.$name.'}', $expanded, $token->value);
            }
        }
        unset($token->extensions['references']);

        return $token->value;
    }

    private function addVirtualPalette(): void
    {
        $keys = [];
        foreach ($this->allTokens as $token) {
            if ('colors' !== ($token->extensions['category'] ?? null) || !isset($token->extensions['colorPalette'])) {
                continue;
            }

            foreach ($token->extensions['colorPaletteTokenKeys'] as $keyPath) {
                $keys[implode('.', $keyPath)] = $keyPath;
            }
            foreach ($token->extensions['colorPaletteRoots'] as $root) {
                if (!($token->extensions['isDefault'] ?? false) || 1 !== \count($root)) {
                    continue;
                }
                $tokenKeys = $token->extensions['colorPaletteTokenKeys'][0] ?? [];
                $keyPath = array_values(array_filter($tokenKeys, static fn (string $part): bool => '' !== $part));
                if ([] !== $keyPath) {
                    $keys[implode('.', [...$root, ...$keyPath])] = [];
                }
            }
        }

        foreach ($keys as $segments) {
            $path = ['colors', 'colorPalette', ...$segments];
            $name = implode('.', array_filter($path, static fn (string $part): bool => '' !== $part));
            $propPath = array_filter(['colorPalette', ...$segments], static fn (string $part): bool => '' !== $part);
            $virtual = new Token($name, $name, $path, [
                'category' => 'colors',
                'prop' => implode('.', $propPath),
                'isVirtual' => true,
            ]);
            $this->registerToken($virtual);
            $this->addCssVariable($virtual);
        }
    }

    private function addConditionalCssVariables(Token $token): void
    {
        if (!\is_string($token->value)) {
            return;
        }

        $references = self::getReferences($token->value);
        if ([] === $references) {
            return;
        }

        if ([] === array_filter($references, static fn (string $reference): bool => str_contains($reference, '/'))) {
            foreach ($references as $reference) {
                $variable = $this->cssVar(explode('.', $reference));
                $token->value = str_replace('{'.$reference.'}', $variable['ref'], $token->value);
            }

            return;
        }

        $token->value = $this->mixColorReferences($token->value);
    }

    private function processColorPalette(Token $token): void
    {
        if (!isset($token->extensions['colorPalette']) || ($token->extensions['isVirtual'] ?? false)) {
            return;
        }

        foreach ($token->extensions['colorPaletteRoots'] as $root) {
            $palette = implode('.', $root);
            $this->colorPalettes[$palette] ??= [];

            $virtual = $this->byName[implode('.', self::replaceRootWithColorPalette($token->path, $root))] ?? null;
            if (null === $virtual) {
                continue;
            }
            $this->colorPalettes[$palette][$virtual->extensions['var']] = $token->extensions['varRef'];

            if (!($token->extensions['isDefault'] ?? false) || 1 !== \count($root)) {
                continue;
            }
            $colorPalette = $this->byName['colors.colorPalette'] ?? null;
            $named = $this->byName[implode('.', $token->path)] ?? null;
            $tokenKeys = $token->extensions['colorPaletteTokenKeys'][0] ?? [];
            $keyPath = array_values(array_filter($tokenKeys, static fn (string $part): bool => '' !== $part));
            if (null === $colorPalette || null === $named || [] === $keyPath) {
                continue;
            }
            $defaultPalette = implode('.', [...$root, ...$keyPath]);
            $this->colorPalettes[$defaultPalette][$colorPalette->extensions['var']] = $named->extensions['varRef'];
        }
    }

    private function processVars(Token $token): void
    {
        $condition = $token->extensions['condition'] ?? null;
        if (
            ($token->extensions['isNegative'] ?? false)
            || (!isset($token->extensions['theme']) && ($token->extensions['isVirtual'] ?? false))
            || !JsValue::isTruthy($condition)
        ) {
            return;
        }

        $this->vars[$condition][$token->extensions['var']] = $token->value;
    }

    private function processValue(Token $token): void
    {
        $category = $token->extensions['category'] ?? null;
        if (!JsValue::isTruthy($category)) {
            return;
        }

        if ($token->extensions['isNegative'] ?? false) {
            $value = 'base' !== $token->extensions['condition'] ? $token->originalValue : $token->value;
        } else {
            $value = $token->extensions['varRef'];
        }

        $this->valuesByCategory[$category][$token->extensions['prop']] = $value;
        $this->values[$token->name] = $value;
    }

    /**
     * Port of `TokenDictionary::colorMix()`.
     *
     * @param callable(string): ?string $resolveColor
     *
     * @return array{invalid: bool, color?: string, value: string}
     */
    private function mix(string $value, callable $resolveColor): array
    {
        $parts = explode('/', $value);
        $color = $parts[0];
        $opacity = $parts[1] ?? '';
        if ('' === $color || '' === $opacity) {
            return ['invalid' => true, 'value' => $color];
        }

        $opacityToken = $this->getValue('opacity.'.$opacity);
        if (!JsValue::isTruthy($opacityToken) && null === JsValue::toNumber($opacity)) {
            return ['invalid' => true, 'value' => $color];
        }

        if (JsValue::isTruthy($opacityToken)) {
            $number = \is_string($opacityToken) ? JsValue::toNumber($opacityToken) : $opacityToken;
            if (!\is_int($number) && !\is_float($number)) {
                throw new InvalidArgumentException(\sprintf('The "opacity.%s" token is not a number.', $opacity));
            }
            $percent = JsValue::toString($number * 100).'%';
        } else {
            $percent = $opacity.'%';
        }
        $color = $resolveColor($color) ?? $color;

        return [
            'invalid' => false,
            'color' => $color,
            'value' => 'color-mix(in srgb, '.$color.' '.$percent.', transparent)',
        ];
    }

    /**
     * Port of `cssVar()` (packages/shared/src/css-var.ts).
     *
     * @param list<string> $path
     *
     * @return array{var: string, ref: string}
     */
    private function cssVar(array $path): array
    {
        $name = preg_replace('/[^a-zA-Z0-9_\x{0081}-\x{10FFFF}-]/u', '\\\\$0', implode('-', $path));
        $parts = array_filter(['-', $this->prefix, $name], static fn (string $part): bool => '' !== $part);
        $variable = preg_replace_callback(
            '/[A-Z]/',
            static fn (array $matches): string => '-'.strtolower($matches[0]),
            implode('-', $parts),
        );

        return ['var' => $variable, 'ref' => 'var('.$variable.')'];
    }

    /**
     * Port of `expandReferences()` (packages/token-dictionary/src/utils.ts).
     *
     * @param callable(string): ?string $resolve
     */
    private function expandCurlyReferences(string $value, callable $resolve): string
    {
        if ([] === self::getReferences($value) && !str_contains($value, 'token(')) {
            return $value;
        }

        foreach (self::getReferences($value) as $reference) {
            $value = str_replace('{'.$reference.'}', $resolve($reference) ?? CssEscaper::escape($reference), $value);
        }
        if (!str_contains($value, 'token(')) {
            return $value;
        }

        return preg_replace_callback('/token\(([^)]+)\)/', static function (array $matches) use ($resolve): string {
            $parts = array_map(JsValue::trim(...), explode(',', $matches[1]));
            $resolved = [];
            foreach ([$parts[0], $parts[1] ?? ''] as $part) {
                if ('' !== $part) {
                    $resolved[] = $resolve($part) ?? CssEscaper::escape($part);
                }
            }
            if (\count($resolved) < 2) {
                return $resolved[0] ?? '';
            }

            if (str_ends_with($resolved[0], ')')) {
                return substr($resolved[0], 0, -1).', '.$resolved[1].')';
            }

            return 'var('.$resolved[0].', '.$resolved[1].')';
        }, $value);
    }

    /**
     * Port of `expandTokenReferences()` (packages/token-dictionary/src/expand-token-references.ts).
     *
     * @param callable(string): ?string $resolve
     */
    private static function expandTokenFunctions(string $value, callable $resolve): string
    {
        $expanded = '';
        $index = 0;
        $length = \strlen($value);
        $state = 'char';
        $tokenPath = '';
        $fallback = '';
        $states = [];

        while ($index < $length) {
            $char = $value[$index];

            if ('{' === $char) {
                $end = strpos($value, '}', $index);
                if (false === $end) {
                    break;
                }

                $path = substr($value, $index + 1, $end - $index - 1);
                $expanded .= $resolve($path) ?? $path;
                $index = $end + 1;
                continue;
            }

            if ('token' === $state && ',' === $char) {
                $state = 'fallback';
                $states[] = $state;
                $resolved = $resolve($tokenPath);
                if (null !== $resolved && str_ends_with($resolved, ')')) {
                    $expanded .= substr($resolved, 0, -1);
                }

                $tokenPath = '';
                $fallback = '';
                continue;
            }

            if ('fallback' === $state && ', var(' === $fallback.$char) {
                $end = self::closingParenthesis(substr($value, $index + 1)) + $index + 1;
                $expanded .= ', var('.substr($value, $index + 1, $end - $index - 1).')';
                $index = $end + 1;
                $state = array_pop($states) ?? $state;
                $fallback = '';
                continue;
            }

            if ('token' === $state || 'fallback' === $state) {
                ++$index;

                if (')' === $char) {
                    $state = array_pop($states) ?? $state;
                    $fallback .= $char;
                    if ('' !== $tokenPath) {
                        $resolved = $resolve($tokenPath) ?? CssEscaper::escape($tokenPath);
                    } else {
                        $resolved = $tokenPath;
                    }

                    $fallback = JsValue::trim(substr($fallback, 1));
                    if (!str_starts_with($fallback, 'token(') && str_ends_with($fallback, ')')) {
                        $fallback = substr($fallback, 0, -1);
                    }
                    if (str_contains($fallback, 'token(')) {
                        $parsed = self::expandTokenFunctions($fallback, $resolve);
                        if ('' !== $parsed) {
                            $fallback = substr($parsed, 0, -1);
                        }
                    } elseif ('' !== $fallback) {
                        $resolvedFallback = $resolve($fallback);
                        if (null !== $resolvedFallback && '' !== $resolvedFallback) {
                            $fallback = $resolvedFallback;
                        }
                    }

                    if ('' === $fallback) {
                        $expanded .= '' !== $resolved ? $resolved : ')';
                    } elseif ('' !== $expanded && !preg_match('/'.JsValue::WHITESPACE.'$/u', $expanded)) {
                        $expanded .= substr($resolved, 0, -1).', '.$fallback.')';
                    } else {
                        $expanded .= $fallback;
                    }

                    $tokenPath = '';
                    $fallback = '';
                    $state = 'char';
                    continue;
                }

                if ('token' === $state) {
                    $tokenPath .= $char;
                } else {
                    $fallback .= $char;
                }
                continue;
            }

            $tokenIndex = strpos($value, 'token(', $index);
            if (false !== $tokenIndex) {
                $expanded .= substr($value, $index, $tokenIndex - $index);
                $index = $tokenIndex + \strlen('token(');
                $state = 'token';
                $states[] = $state;
                continue;
            }

            $expanded .= $char;
            ++$index;
        }

        return $expanded;
    }

    private static function closingParenthesis(string $value): int
    {
        $depth = 1;
        $length = \strlen($value);
        for ($index = 0; $index < $length; ++$index) {
            if ('(' === $value[$index]) {
                ++$depth;
            } elseif (')' === $value[$index] && 0 === --$depth) {
                return $index;
            }
        }

        return $length;
    }

    /**
     * @return list<string>
     */
    private static function getReferences(string $value): array
    {
        preg_match_all('/\{([^}]*)\}/', $value, $matches);

        return array_map(
            static fn (string $match): string => JsValue::trim(str_replace(['{', '}'], '', $match)),
            $matches[0],
        );
    }

    /**
     * Port of `walkObject()` with `isToken` as the stop condition.
     *
     * @param list<string>                        $path
     * @param callable(mixed, list<string>): void $callback
     */
    private static function walkTokens(mixed $value, array $path, callable $callback): void
    {
        if (!\is_array($value)) {
            $callback($value, $path);

            return;
        }
        if ([] !== $value && !array_is_list($value) && \array_key_exists('value', $value)) {
            $callback($value, $path);

            return;
        }

        foreach ($value as $key => $child) {
            self::walkTokens($child, [...$path, (string) $key], $callback);
        }
    }

    /**
     * @param list<string>                        $path
     * @param callable(mixed, list<string>): void $callback
     */
    private static function walkLeaves(mixed $value, array $path, callable $callback): void
    {
        if (!\is_array($value)) {
            $callback($value, $path);

            return;
        }

        foreach ($value as $key => $child) {
            self::walkLeaves($child, [...$path, (string) $key], $callback);
        }
    }

    /**
     * @param callable(mixed): mixed $callback
     */
    private static function mapConditions(mixed $conditions, callable $callback): mixed
    {
        if (!\is_array($conditions)) {
            return $callback($conditions);
        }
        if ([] === $conditions) {
            return [];
        }
        if (self::isComposite($conditions)) {
            return $callback($conditions);
        }

        return array_map(
            static fn (mixed $condition): mixed => self::mapConditions($condition, $callback),
            $conditions,
        );
    }

    /**
     * @param list<string> $path
     *
     * @return array{list<string>, bool}
     */
    private static function filterDefault(array $path): array
    {
        $isDefault = \in_array('DEFAULT', $path, true);
        if ('DEFAULT' !== ($path[0] ?? null)) {
            $path = array_values(array_filter($path, static fn (string $part): bool => 'DEFAULT' !== $part));
        }

        return [$path, $isDefault];
    }

    /**
     * @return array{value: mixed, extensions?: array<string, mixed>}
     */
    private static function assertToken(mixed $token): array
    {
        if (!\is_array($token) || !\array_key_exists('value', $token)) {
            throw new InvalidArgumentException(\sprintf('Invalid token format: %s', json_encode($token)));
        }

        return $token;
    }

    /**
     * @param list<string> $path
     * @param list<string> $root
     *
     * @return list<string>
     */
    private static function replaceRootWithColorPalette(array $path, array $root): array
    {
        foreach (array_keys($path) as $start) {
            if (\array_slice($path, $start, \count($root)) === $root) {
                array_splice($path, $start, \count($root), ['colorPalette']);

                return $path;
            }
        }

        return $path;
    }

    /**
     * @param list<mixed> $values
     */
    private static function join(array $values, string $separator): string
    {
        $toString = static function (mixed $value): string {
            if (null === $value) {
                return '';
            }
            if (\is_scalar($value)) {
                return JsValue::toString($value);
            }

            throw new InvalidArgumentException(\sprintf('Cannot join %s in a token value.', json_encode($value)));
        };

        return implode($separator, array_map($toString, $values));
    }

    /**
     * @param array<array-key, mixed> $value
     */
    private static function isCompositeShadow(array $value): bool
    {
        foreach (['offsetX', 'offsetY', 'blur', 'spread'] as $key) {
            $length = $value[$key] ?? null;
            if (!\is_string($length) && !\is_int($length) && !\is_float($length)) {
                return false;
            }
        }

        return \is_string($value['color'] ?? null)
            && (!\array_key_exists('inset', $value) || \is_bool($value['inset']));
    }

    /**
     * @param array<array-key, mixed> $value
     */
    private static function isCompositeGradient(array $value): bool
    {
        if (
            !\is_string($value['type'] ?? null)
            || !\is_string($value['placement'] ?? null)
            || !\is_array($value['stops'] ?? null)
            || !array_is_list($value['stops'])
        ) {
            return false;
        }

        $strings = array_filter($value['stops'], \is_string(...));
        if (\count($strings) === \count($value['stops'])) {
            return true;
        }
        if ([] !== $strings) {
            return false;
        }

        $isInvalidStop = static fn (mixed $stop): bool => !\is_array($stop)
            || !\is_string($stop['color'] ?? null)
            || (!\is_int($stop['position'] ?? null) && !\is_float($stop['position'] ?? null));

        return [] === array_filter($value['stops'], $isInvalidStop);
    }

    /**
     * @param array<array-key, mixed> $value
     */
    private static function isCompositeBorder(array $value): bool
    {
        $width = $value['width'] ?? null;

        return \is_string($value['color'] ?? null)
            && \is_string($value['style'] ?? null)
            && (\is_string($width) || \is_int($width) || \is_float($width));
    }

    /**
     * @param array<array-key, mixed> $value
     */
    private static function isCompositeAsset(array $value): bool
    {
        return \in_array($value['type'] ?? null, ['url', 'svg'], true) && \is_string($value['value'] ?? null);
    }
}
