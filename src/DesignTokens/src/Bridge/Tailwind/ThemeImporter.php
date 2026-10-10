<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Bridge\Tailwind;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\UX\DesignTokens\Exception\InvalidArgumentException;
use Symfony\UX\DesignTokens\Importer\ImporterInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class ThemeImporter implements ImporterInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var array<string, array{string, 'color'|'dimension'|'dimension|number'|'number'|'fontFamily'|'fontWeight'|'cubicBezier'|'shadow'}> */
    private const NAMESPACES = [
        'inset-shadow' => ['inset-shadow', 'shadow'],
        'tab-size' => ['tab-size', 'number'],
        'font-weight' => ['font-weight', 'fontWeight'],
        'breakpoint' => ['breakpoint', 'dimension'],
        'perspective' => ['perspective', 'dimension'],
        'container' => ['container', 'dimension'],
        'spacing' => ['spacing', 'dimension'],
        'tracking' => ['tracking', 'dimension|number'],
        'leading' => ['leading', 'dimension|number'],
        'radius' => ['radius', 'dimension'],
        'shadow' => ['shadow', 'shadow'],
        'color' => ['color', 'color'],
        'font' => ['font', 'fontFamily'],
        'text' => ['text', 'dimension'],
        'blur' => ['blur', 'dimension'],
        'zoom' => ['zoom', 'number'],
        'aspect' => ['aspect', 'number'],
        'ease' => ['ease', 'cubicBezier'],
    ];

    private const UNSUPPORTED_PREFIXES = ['--text-shadow', '--drop-shadow', '--animate', '--default'];

    public function import(string $contents, array $context = []): array
    {
        $declarations = $this->declarations($contents);
        $variables = [];

        foreach ($declarations as $name => $value) {
            if ('initial' === strtolower(trim($value))) {
                continue;
            }
            try {
                $variables[$name] = $this->variable($name);
            } catch (\InvalidArgumentException $e) {
                $this->logger?->notice($e->getMessage(), ['variable' => $name]);
            }
        }

        $tokens = [];
        foreach ($variables as $name => [$group, $tokenName, $type]) {
            $value = trim($declarations[$name]);
            try {
                $nativeValue = $this->value($name, $value, $type, $variables);
            } catch (\InvalidArgumentException $e) {
                $this->logger?->warning($e->getMessage(), ['variable' => $name]);
                continue;
            }
            $tokens[$group][$tokenName] = [
                '$type' => 'dimension|number' === $type ? (\is_array($nativeValue) ? 'dimension' : 'number') : $type,
                '$value' => $nativeValue,
            ];
        }

        return $tokens;
    }

    /** @return array<string, string> */
    private function declarations(string $css): array
    {
        $declarations = [];
        $offset = 0;
        $found = false;

        while (null !== $start = $this->findTheme($css, $offset)) {
            $found = true;
            $open = $this->findThemeOpeningBrace($css, $start + 6);
            $close = $this->matchingBrace($css, $open);
            foreach ($this->parseBlock(substr($css, $open + 1, $close - $open - 1)) as $name => $value) {
                $declarations[$name] = $value;
            }
            $offset = $close + 1;
        }

        if (!$found) {
            throw new InvalidArgumentException('No top-level Tailwind @theme block was found.');
        }

        return $declarations;
    }

    private function findTheme(string $css, int $offset): ?int
    {
        $length = \strlen($css);
        $depth = 0;
        for ($index = $offset; $index < $length; ++$index) {
            if ('/' === $css[$index] && '*' === ($css[$index + 1] ?? '')) {
                $index = $this->commentEnd($css, $index) - 1;
                continue;
            }
            if ('"' === $css[$index] || "'" === $css[$index]) {
                $index = $this->stringEnd($css, $index);
                continue;
            }
            if ('{' === $css[$index]) {
                ++$depth;
                continue;
            }
            if ('}' === $css[$index]) {
                --$depth;
                continue;
            }
            if (0 === $depth && '@theme' === substr($css, $index, 6)
                && (0 === $index || !preg_match('/[\w-]/', $css[$index - 1]))
                && !preg_match('/[\w-]/', $css[$index + 6] ?? '')) {
                return $index;
            }
        }

        return null;
    }

    private function findThemeOpeningBrace(string $css, int $offset): int
    {
        $length = \strlen($css);
        for ($index = $offset; $index < $length; ++$index) {
            if ('{' === $css[$index]) {
                $options = trim(substr($css, $offset, $index - $offset));
                if ('' !== $options && 1 !== preg_match('/^(?:(?:static|inline|default|reference)\s*)+$/', $options)) {
                    throw new InvalidArgumentException(\sprintf('Unsupported @theme options "%s".', $options));
                }

                return $index;
            }
            if (';' === $css[$index] || '}' === $css[$index]) {
                break;
            }
        }

        throw new InvalidArgumentException('Malformed Tailwind @theme block.');
    }

    private function matchingBrace(string $css, int $open): int
    {
        $depth = 1;
        $length = \strlen($css);
        for ($index = $open + 1; $index < $length; ++$index) {
            if ('/' === $css[$index] && '*' === ($css[$index + 1] ?? '')) {
                $index = $this->commentEnd($css, $index) - 1;
                continue;
            }
            if ('"' === $css[$index] || "'" === $css[$index]) {
                $index = $this->stringEnd($css, $index);
                continue;
            }
            if ('{' === $css[$index]) {
                ++$depth;
            } elseif ('}' === $css[$index] && 0 === --$depth) {
                return $index;
            }
        }

        throw new InvalidArgumentException('Unterminated Tailwind @theme block.');
    }

    /** @return array<string, string> */
    private function parseBlock(string $body): array
    {
        $result = [];
        $length = \strlen($body);
        $index = 0;

        while ($index < $length) {
            $this->skipWhitespaceAndComments($body, $index);
            if ($index >= $length) {
                break;
            }
            if ('@' === $body[$index]) {
                $index = $this->skipAtRule($body, $index);
                continue;
            }
            if ('--' !== substr($body, $index, 2)) {
                throw new InvalidArgumentException(\sprintf('Unexpected content in @theme near "%s".', substr($body, $index, 24)));
            }

            $colon = strpos($body, ':', $index + 2);
            if (false === $colon) {
                throw new InvalidArgumentException('Malformed Tailwind theme declaration.');
            }
            $name = trim(substr($body, $index, $colon - $index));
            if (1 !== preg_match('/^--(?:[a-zA-Z0-9_-]+\*?|\*)$/', $name)) {
                throw new InvalidArgumentException(\sprintf('Invalid Tailwind theme variable "%s".', $name));
            }
            [$value, $index] = $this->declarationValue($body, $colon + 1);
            $result[$name] = trim($value);
        }

        return $result;
    }

    private function skipWhitespaceAndComments(string $css, int &$index): void
    {
        $length = \strlen($css);
        while ($index < $length) {
            if (ctype_space($css[$index]) || ';' === $css[$index]) {
                ++$index;
                continue;
            }
            if ('/' === $css[$index] && '*' === ($css[$index + 1] ?? '')) {
                $index = $this->commentEnd($css, $index);
                continue;
            }
            break;
        }
    }

    private function skipAtRule(string $css, int $index): int
    {
        $length = \strlen($css);
        for (; $index < $length; ++$index) {
            if (';' === $css[$index]) {
                return $index + 1;
            }
            if ('{' === $css[$index]) {
                return $this->matchingBrace($css, $index) + 1;
            }
        }

        throw new InvalidArgumentException('Unterminated at-rule inside @theme.');
    }

    /** @return array{string, int} */
    private function declarationValue(string $css, int $offset): array
    {
        $parentheses = 0;
        $length = \strlen($css);
        for ($index = $offset; $index < $length; ++$index) {
            if ('"' === $css[$index] || "'" === $css[$index]) {
                $index = $this->stringEnd($css, $index);
                continue;
            }
            if ('(' === $css[$index]) {
                ++$parentheses;
            } elseif (')' === $css[$index]) {
                --$parentheses;
            } elseif (';' === $css[$index] && 0 === $parentheses) {
                return [substr($css, $offset, $index - $offset), $index + 1];
            } elseif ('{' === $css[$index] || '}' === $css[$index]) {
                throw new InvalidArgumentException('Malformed Tailwind theme declaration.');
            }
        }

        // The last declaration of a block may omit its semicolon.
        if (0 === $parentheses) {
            return [substr($css, $offset), $length];
        }

        throw new InvalidArgumentException('Unbalanced parentheses in a Tailwind theme declaration.');
    }

    private function stringEnd(string $css, int $start): int
    {
        $quote = $css[$start];
        $length = \strlen($css);
        for ($index = $start + 1; $index < $length; ++$index) {
            if ('\\' === $css[$index]) {
                ++$index;
            } elseif ($quote === $css[$index]) {
                return $index;
            }
        }

        throw new InvalidArgumentException('Unterminated CSS string.');
    }

    private function commentEnd(string $css, int $start): int
    {
        $end = strpos($css, '*/', $start + 2);
        if (false === $end) {
            throw new InvalidArgumentException('Unterminated CSS comment.');
        }

        return $end + 2;
    }

    /** @return array{string, string, 'color'|'dimension'|'dimension|number'|'number'|'fontFamily'|'fontWeight'|'cubicBezier'|'shadow'} */
    private function variable(string $name): array
    {
        foreach (self::UNSUPPORTED_PREFIXES as $prefix) {
            if ($name === $prefix || str_starts_with($name, $prefix.'-')) {
                throw new InvalidArgumentException(\sprintf('Tailwind theme variable "%s" has no DTCG type.', $name));
            }
        }

        foreach (self::NAMESPACES as $namespace => [$group, $type]) {
            $prefix = '--'.$namespace;
            // A bare --spacing is the group's own value (Format 6.2).
            if ($name === $prefix) {
                return [$group, '$root', $type];
            }
            if (str_starts_with($name, $prefix.'-')) {
                $tokenName = substr($name, \strlen($prefix) + 1);
                if ('' === $tokenName || str_contains($tokenName, '*')) {
                    break;
                }
                // A --*--modifier variable modifies another one; it is not a token.
                if (str_contains($tokenName, '--')) {
                    throw new InvalidArgumentException(\sprintf('Tailwind theme variable "%s" modifies another variable and has no DTCG type.', $name));
                }

                return [$group, $tokenName, $type];
            }
        }

        throw new InvalidArgumentException(\sprintf('Unsupported Tailwind theme variable "%s".', $name));
    }

    /**
     * @param 'color'|'dimension'|'dimension|number'|'number'|'fontFamily'|'fontWeight'|'cubicBezier'|'shadow'                                       $type
     * @param array<string, array{string, string, 'color'|'dimension'|'dimension|number'|'number'|'fontFamily'|'fontWeight'|'cubicBezier'|'shadow'}> $variables
     */
    private function value(string $name, string $value, string $type, array $variables): mixed
    {
        if (1 === preg_match('/^var\(\s*(--[a-zA-Z0-9_-]+)\s*\)$/', $value, $matches)) {
            $target = $variables[$matches[1]] ?? null;
            if (null === $target) {
                throw new InvalidArgumentException(\sprintf('Tailwind theme variable "%s" references unknown or non-importable variable "%s".', $name, $matches[1]));
            }

            return '{'.$target[0].'.'.$target[1].'}';
        }
        if (str_contains($value, 'var(') || str_contains($value, 'calc(')) {
            throw new InvalidArgumentException(\sprintf('Tailwind theme variable "%s" uses a CSS expression that cannot be represented losslessly in DTCG.', $name));
        }

        return match ($type) {
            'color' => $this->color($value, $name),
            'dimension' => $this->dimension($value, $name),
            'dimension|number' => $this->dimensionOrNumber($value, $name),
            'number' => $this->number($value, $name),
            'fontFamily' => $this->fontFamily($value, $name),
            'fontWeight' => $this->fontWeight($value, $name),
            'cubicBezier' => $this->cubicBezier($value, $name),
            'shadow' => $this->shadow($value, $name),
        };
    }

    /** @return array{value: int|float, unit: string} */
    private function dimension(string $value, string $name): array
    {
        if (1 === preg_match('/^([+-]?(?:\d+(?:\.\d*)?|\.\d+))\s*(px|rem)$/i', $value, $matches)) {
            return ['value' => $this->numeric($matches[1]), 'unit' => strtolower($matches[2])];
        }
        if ('0' === trim($value)) {
            return ['value' => 0, 'unit' => 'px'];
        }
        if ($this->hasOtherUnit($value)) {
            throw new InvalidArgumentException(\sprintf('Tailwind theme variable "%s" uses a unit DTCG does not have (Format 8.2 allows px and rem).', $name));
        }

        throw new InvalidArgumentException(\sprintf('Tailwind theme variable "%s" must use a DTCG dimension unit (px or rem).', $name));
    }

    private function dimensionOrNumber(string $value, string $name): mixed
    {
        try {
            return $this->dimension($value, $name);
        } catch (\InvalidArgumentException $e) {
            if ($this->hasOtherUnit($value)) {
                throw $e;
            }

            return $this->number($value, $name);
        }
    }

    /** A number followed by a CSS unit other than px or rem, such as -0.025em. */
    private function hasOtherUnit(string $value): bool
    {
        return 1 === preg_match('/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)\s*(?!px$|rem$)[a-z%]+$/i', trim($value));
    }

    private function number(string $value, string $name): int|float
    {
        // A ratio such as "16 / 9" is the number CSS computes for aspect-ratio.
        if (1 === preg_match('/^(\d+(?:\.\d+)?)\s*\/\s*(\d+(?:\.\d+)?)$/', trim($value), $ratio) && 0.0 !== (float) $ratio[2]) {
            return $this->numeric((string) ((float) $ratio[1] / (float) $ratio[2]));
        }
        if (1 !== preg_match('/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)$/', trim($value))) {
            throw new InvalidArgumentException(\sprintf('Tailwind theme variable "%s" must be a finite number.', $name));
        }

        return $this->numeric($value);
    }

    private function numeric(string $value): int|float
    {
        $number = (float) trim($value);

        return floor($number) === $number && $number <= \PHP_INT_MAX && $number >= \PHP_INT_MIN ? (int) $number : $number;
    }

    /** @return string|list<string> */
    private function fontFamily(string $value, string $name): string|array
    {
        $families = array_map(\trim(...), $this->splitTopLevel($value, ','));
        $families = array_map(static function (string $family) use ($name): string {
            if ('' === $family) {
                throw new InvalidArgumentException(\sprintf('Tailwind theme variable "%s" contains an empty font family.', $name));
            }
            if ((str_starts_with($family, '"') && str_ends_with($family, '"'))
                || (str_starts_with($family, "'") && str_ends_with($family, "'"))) {
                return stripcslashes(substr($family, 1, -1));
            }
            if (1 !== preg_match('/^[\p{L}\p{N}_ -]+$/u', $family)) {
                throw new InvalidArgumentException(\sprintf('Tailwind theme variable "%s" contains an unsupported font family.', $name));
            }

            return $family;
        }, $families);

        return 1 === \count($families) ? $families[0] : $families;
    }

    private function fontWeight(string $value, string $name): int|string
    {
        if (1 === preg_match('/^\d+(?:\.0+)?$/', trim($value))) {
            $weight = (int) $value;
            if ($weight >= 1 && $weight <= 1000) {
                return $weight;
            }
        }
        $keyword = strtolower(trim($value));
        $keyword = [
            'extralight' => 'extra-light', 'ultralight' => 'ultra-light',
            'semibold' => 'semi-bold', 'demibold' => 'demi-bold',
            'extrabold' => 'extra-bold', 'ultrabold' => 'ultra-bold',
            'extrablack' => 'extra-black', 'ultrablack' => 'ultra-black',
        ][$keyword] ?? $keyword;
        if (\in_array($keyword, ['thin', 'hairline', 'extra-light', 'ultra-light', 'light', 'normal', 'regular', 'book', 'medium', 'semi-bold', 'demi-bold', 'bold', 'extra-bold', 'ultra-bold', 'black', 'heavy', 'extra-black', 'ultra-black'], true)) {
            return $keyword;
        }

        throw new InvalidArgumentException(\sprintf('Tailwind theme variable "%s" is not a DTCG font weight.', $name));
    }

    /** @return list<int|float> */
    private function cubicBezier(string $value, string $name): array
    {
        if (1 !== preg_match('/^cubic-bezier\((.*)\)$/i', trim($value), $matches)) {
            throw new InvalidArgumentException(\sprintf('Tailwind theme variable "%s" must use cubic-bezier().', $name));
        }
        $parts = array_map(\trim(...), explode(',', $matches[1]));
        if (4 !== \count($parts)) {
            throw new InvalidArgumentException(\sprintf('Tailwind theme variable "%s" must contain four cubic Bezier coordinates.', $name));
        }

        return array_map(fn (string $part): int|float => $this->number($part, $name), $parts);
    }

    /** @return array<string, mixed> */
    private function color(string $value, string $name): array
    {
        $value = trim($value);
        if (1 === preg_match('/^#([0-9a-f]{3,8})$/i', $value, $matches)) {
            return $this->hexColor($matches[1], $name);
        }
        if (1 !== preg_match('/^([a-z0-9-]+)\((.*)\)$/i', $value, $matches)) {
            throw new InvalidArgumentException(\sprintf('Tailwind theme variable "%s" must use a structured CSS color.', $name));
        }
        $function = strtolower($matches[1]);
        [$componentsText, $alphaText] = $this->colorParts($matches[2]);

        if ('rgb' === $function || 'rgba' === $function) {
            $components = $this->spaceComponents($componentsText, $name);
            if (3 !== \count($components)) {
                throw new InvalidArgumentException(\sprintf('Tailwind theme variable "%s" must contain three color components.', $name));
            }
            $components = array_map(function (string $component) use ($name): int|float {
                if (str_ends_with($component, '%')) {
                    return $this->number(substr($component, 0, -1), $name) / 100;
                }

                return $this->number($component, $name) / 255;
            }, $components);

            return $this->colorValue('srgb', $components, $alphaText, $name);
        }

        $space = 'color' === $function ? $this->takeColorSpace($componentsText, $name) : $function;
        if (!\in_array($space, ['srgb', 'srgb-linear', 'hsl', 'hwb', 'lab', 'lch', 'oklab', 'oklch', 'display-p3', 'a98-rgb', 'prophoto-rgb', 'rec2020', 'xyz-d65', 'xyz-d50'], true)) {
            throw new InvalidArgumentException(\sprintf('Tailwind theme variable "%s" uses unsupported color space "%s".', $name, $space));
        }
        $parts = $this->spaceComponents($componentsText, $name);
        if (3 !== \count($parts)) {
            throw new InvalidArgumentException(\sprintf('Tailwind theme variable "%s" must contain three color components.', $name));
        }
        $components = [];
        foreach ($parts as $index => $part) {
            if ('none' === $part) {
                $components[] = 'none';
                continue;
            }
            $percent = str_ends_with($part, '%');
            $numeric = $this->number($percent ? substr($part, 0, -1) : $part, $name);
            $components[] = $percent && (\in_array($space, ['srgb', 'srgb-linear', 'display-p3', 'a98-rgb', 'prophoto-rgb', 'rec2020', 'xyz-d65', 'xyz-d50'], true)
                || (\in_array($space, ['oklab', 'oklch'], true) && 0 === $index)) ? $numeric / 100 : $numeric;
        }

        return $this->colorValue($space, $components, $alphaText, $name);
    }

    /** @return array<string, mixed> */
    private function hexColor(string $hex, string $name): array
    {
        $length = \strlen($hex);
        if (!\in_array($length, [3, 4, 6, 8], true)) {
            throw new InvalidArgumentException(\sprintf('Tailwind theme variable "%s" contains an invalid hexadecimal color.', $name));
        }
        if ($length <= 4) {
            $hex = implode('', array_map(static fn (string $digit): string => $digit.$digit, str_split($hex)));
        }
        $rgb = substr($hex, 0, 6);
        $result = [
            'colorSpace' => 'srgb',
            'components' => array_map(static fn (string $pair): float => hexdec($pair) / 255, str_split($rgb, 2)),
            'hex' => '#'.strtolower($rgb),
        ];
        if (8 === \strlen($hex)) {
            $result['alpha'] = hexdec(substr($hex, 6, 2)) / 255;
        }

        return $result;
    }

    /** @return array{string, ?string} */
    private function colorParts(string $contents): array
    {
        $parts = $this->splitTopLevel($contents, '/');
        if (\count($parts) > 2) {
            throw new InvalidArgumentException('A CSS color may only contain one alpha separator.');
        }

        return [trim($parts[0]), isset($parts[1]) ? trim($parts[1]) : null];
    }

    private function takeColorSpace(string &$contents, string $name): string
    {
        if (1 !== preg_match('/^([a-z0-9-]+)\s+(.+)$/i', $contents, $matches)) {
            throw new InvalidArgumentException(\sprintf('Tailwind theme variable "%s" has a malformed color() value.', $name));
        }
        $contents = $matches[2];

        return strtolower($matches[1]);
    }

    /**
     * @param list<int|float|string> $components
     *
     * @return array{colorSpace: string, components: list<int|float|string>, alpha?: int|float}
     */
    private function colorValue(string $space, array $components, ?string $alpha, string $name): array
    {
        $result = ['colorSpace' => $space, 'components' => $components];
        if (null !== $alpha) {
            $percent = str_ends_with($alpha, '%');
            $result['alpha'] = $this->number($percent ? substr($alpha, 0, -1) : $alpha, $name) / ($percent ? 100 : 1);
        }

        return $result;
    }

    /** @return list<string> */
    private function spaceComponents(string $value, string $name): array
    {
        $parts = preg_split('/\s+/', trim($value));
        if (false === $parts || [''] === $parts) {
            throw new InvalidArgumentException(\sprintf('Tailwind theme variable "%s" has no value.', $name));
        }

        return $parts;
    }

    /** @return array<string, mixed>|list<array<string, mixed>> */
    private function shadow(string $value, string $name): array
    {
        $layers = array_map(fn (string $layer): array => $this->shadowLayer(trim($layer), $name), $this->splitTopLevel($value, ','));

        return 1 === \count($layers) ? $layers[0] : $layers;
    }

    /** @return array<string, mixed> */
    private function shadowLayer(string $layer, string $name): array
    {
        $parts = $this->splitWhitespace($layer);
        $inset = false;
        $color = null;
        $dimensions = [];
        foreach ($parts as $part) {
            if ('inset' === strtolower($part)) {
                $inset = true;
            } elseif (str_starts_with($part, '#') || 1 === preg_match('/^[a-z0-9-]+\(/i', $part)) {
                if (null !== $color) {
                    throw new InvalidArgumentException(\sprintf('Tailwind theme variable "%s" contains multiple shadow colors.', $name));
                }
                $color = $this->color($part, $name);
            } else {
                $dimensions[] = $this->dimension($part, $name);
            }
        }
        if (null === $color || \count($dimensions) < 2 || \count($dimensions) > 4) {
            throw new InvalidArgumentException(\sprintf('Tailwind theme variable "%s" is not a lossless CSS box shadow.', $name));
        }
        while (\count($dimensions) < 4) {
            $dimensions[] = ['value' => 0, 'unit' => 'px'];
        }

        return [
            'color' => $color,
            'offsetX' => $dimensions[0],
            'offsetY' => $dimensions[1],
            'blur' => $dimensions[2],
            'spread' => $dimensions[3],
            'inset' => $inset,
        ];
    }

    /** @return list<string> */
    private function splitWhitespace(string $value): array
    {
        $parts = [];
        $start = 0;
        $depth = 0;
        $length = \strlen($value);
        for ($index = 0; $index <= $length; ++$index) {
            $character = $value[$index] ?? ' ';
            if ('(' === $character) {
                ++$depth;
            } elseif (')' === $character) {
                --$depth;
            } elseif (ctype_space($character) && 0 === $depth) {
                if ($index > $start) {
                    $parts[] = substr($value, $start, $index - $start);
                }
                $start = $index + 1;
            }
        }

        return $parts;
    }

    /** @return list<string> */
    private function splitTopLevel(string $value, string $separator): array
    {
        $parts = [];
        $start = 0;
        $depth = 0;
        $quote = null;
        $length = \strlen($value);
        for ($index = 0; $index < $length; ++$index) {
            $character = $value[$index];
            if (null !== $quote) {
                if ('\\' === $character) {
                    ++$index;
                } elseif ($quote === $character) {
                    $quote = null;
                }
                continue;
            }
            if ('"' === $character || "'" === $character) {
                $quote = $character;
            } elseif ('(' === $character) {
                ++$depth;
            } elseif (')' === $character) {
                --$depth;
            } elseif ($separator === $character && 0 === $depth) {
                $parts[] = substr($value, $start, $index - $start);
                $start = $index + 1;
            }
        }
        $parts[] = substr($value, $start);

        return $parts;
    }
}
