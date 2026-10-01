<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Validation;

use Symfony\UX\DesignTokens\Exception\InvalidArgumentException;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class TokenValueValidator
{
    /** @var list<string> */
    public const TYPES = [
        'color', 'dimension', 'fontFamily', 'fontWeight', 'duration',
        'cubicBezier', 'number', 'strokeStyle', 'border', 'transition',
        'shadow', 'gradient', 'typography',
    ];

    private const COLOR_SPACES = [
        'srgb', 'srgb-linear', 'hsl', 'hwb', 'lab', 'lch', 'oklab',
        'oklch', 'display-p3', 'a98-rgb', 'prophoto-rgb', 'rec2020',
        'xyz-d65', 'xyz-d50',
    ];

    public const FONT_WEIGHTS = [
        'thin', 'hairline', 'extra-light', 'ultra-light', 'light', 'normal',
        'regular', 'book', 'medium', 'semi-bold', 'demi-bold', 'bold',
        'extra-bold', 'ultra-bold', 'black', 'heavy', 'extra-black',
        'ultra-black',
    ];

    public function validate(string $type, mixed $value, string $path = ''): void
    {
        if (!\in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException(\sprintf('Unknown DTCG token type "%s" at "%s".', $type, $path));
        }

        match ($type) {
            'color' => $this->color($value, $path),
            'dimension' => $this->unit($value, ['px', 'rem'], $path, 'dimension'),
            'duration' => $this->unit($value, ['ms', 's'], $path, 'duration'),
            'number' => $this->number($value, $path),
            'fontFamily' => $this->fontFamily($value, $path),
            'fontWeight' => $this->fontWeight($value, $path),
            'cubicBezier' => $this->cubicBezier($value, $path),
            'strokeStyle' => $this->strokeStyle($value, $path),
            'gradient' => $this->gradient($value, $path),
            'border' => $this->border($value, $path),
            'shadow' => $this->shadow($value, $path),
            'transition' => $this->transition($value, $path),
            'typography' => $this->typography($value, $path),
        };
    }

    private function color(mixed $value, string $path): void
    {
        if (!\is_array($value) || array_is_list($value)) {
            $this->fail('a structured color', $path);
        }
        $this->keys($value, ['colorSpace', 'components'], ['alpha', 'hex'], $path);

        $space = $value['colorSpace'] ?? null;
        if (!\is_string($space) || !\in_array($space, self::COLOR_SPACES, true)) {
            $this->fail('a supported color space', $path.'.colorSpace');
        }

        $components = $value['components'] ?? null;
        if (!\is_array($components) || !array_is_list($components) || 3 !== \count($components)) {
            $this->fail('exactly three color components', $path.'.components');
        }
        foreach ($components as $index => $component) {
            if ('none' === $component) {
                continue;
            }
            if ((!\is_int($component) && !\is_float($component)) || (\is_float($component) && !is_finite($component))) {
                $this->fail(\sprintf('a numeric color component at index %d', $index), $path.'.components');
            }
            // Component ranges are advice (Color 4.2); only the hue has a hard rule.
            if (!$this->validHue($space, $index, $component)) {
                $this->fail(\sprintf('a hue below 360 at index %d', $index), $path.'.components');
            }
        }

        if (\array_key_exists('alpha', $value)
            && (!$this->isNumber($value['alpha']) || $value['alpha'] < 0 || $value['alpha'] > 1)) {
            $this->fail('an alpha value between 0 and 1', $path.'.alpha');
        }
        if (\array_key_exists('hex', $value)
            && (!\is_string($value['hex']) || 1 !== preg_match('/^#[0-9a-fA-F]{6}$/D', $value['hex']))) {
            $this->fail('a six-digit hexadecimal color fallback', $path.'.hex');
        }
    }

    /** Color 4.2: "360 MUST NOT be used" for a hue. */
    private function validHue(string $space, int $index, int|float $component): bool
    {
        return match (true) {
            \in_array($space, ['hsl', 'hwb'], true) && 0 === $index,
            \in_array($space, ['lch', 'oklch'], true) && 2 === $index => $component >= 0 && $component < 360,
            default => true,
        };
    }

    /** @param list<string> $units */
    private function unit(mixed $value, array $units, string $path, string $type): void
    {
        if (!\is_array($value) || array_is_list($value)) {
            $this->fail(\sprintf('a structured %s', $type), $path);
        }
        $this->keys($value, ['value', 'unit'], [], $path);
        if (!$this->isNumber($value['value'])) {
            $this->fail('a numeric value', $path.'.value');
        }
        if (!\in_array($value['unit'], $units, true)) {
            $this->fail('unit '.implode('|', $units), $path.'.unit');
        }
    }

    private function number(mixed $value, string $path): void
    {
        if (!$this->isNumber($value)) {
            $this->fail('a finite number', $path);
        }
    }

    private function fontFamily(mixed $value, string $path): void
    {
        if (\is_string($value) && !$this->isCurlyReference($value)) {
            return;
        }
        if (!\is_array($value) || !array_is_list($value) || [] === $value) {
            $this->fail('a string or non-empty list of strings', $path);
        }
        foreach ($value as $index => $family) {
            if (!\is_string($family) || $this->isCurlyReference($family)) {
                $this->fail('a string font family', $path.'['.$index.']');
            }
        }
    }

    private function fontWeight(mixed $value, string $path): void
    {
        if ($this->isNumber($value) && $value >= 1 && $value <= 1000) {
            return;
        }
        if (\is_string($value) && \in_array($value, self::FONT_WEIGHTS, true)) {
            return;
        }
        $this->fail('a font weight from 1 to 1000 or a standard keyword', $path);
    }

    private function cubicBezier(mixed $value, string $path): void
    {
        if (!\is_array($value) || !array_is_list($value) || 4 !== \count($value)) {
            $this->fail('a list of four cubic Bezier coordinates', $path);
        }
        foreach ($value as $index => $coordinate) {
            if (!$this->isNumber($coordinate) || (0 === $index % 2 && ($coordinate < 0 || $coordinate > 1))) {
                $this->fail('a valid cubic Bezier coordinate', $path.'['.$index.']');
            }
        }
    }

    private function strokeStyle(mixed $value, string $path): void
    {
        if (\is_string($value)) {
            if (!\in_array($value, ['solid', 'dashed', 'dotted', 'double', 'groove', 'ridge', 'outset', 'inset'], true)) {
                $this->fail('a valid stroke style keyword', $path);
            }

            return;
        }
        if (!\is_array($value) || array_is_list($value)) {
            $this->fail('a structured stroke style', $path);
        }
        $this->keys($value, ['dashArray', 'lineCap'], [], $path);
        if (!\is_array($value['dashArray']) || !array_is_list($value['dashArray']) || [] === $value['dashArray']) {
            $this->fail('a non-empty stroke dash array', $path.'.dashArray');
        }
        foreach ($value['dashArray'] as $index => $dimension) {
            $this->unit($dimension, ['px', 'rem'], $path.'.dashArray['.$index.']', 'dimension');
        }
        if (!\in_array($value['lineCap'], ['butt', 'round', 'square'], true)) {
            $this->fail('a valid line cap', $path.'.lineCap');
        }
    }

    private function gradient(mixed $value, string $path): void
    {
        if (!\is_array($value) || !array_is_list($value) || [] === $value) {
            $this->fail('a non-empty list of gradient stops', $path);
        }
        foreach ($value as $index => $stop) {
            if (!\is_array($stop) || array_is_list($stop)) {
                $this->fail('a gradient stop', $path.'['.$index.']');
            }
            $this->keys($stop, ['color', 'position'], [], $path.'['.$index.']');
            $this->color($stop['color'], $path.'['.$index.'].color');
            if (!$this->isNumber($stop['position'])) {
                $this->fail('a finite gradient position', $path.'['.$index.'].position');
            }
        }
    }

    private function border(mixed $value, string $path): void
    {
        if (!\is_array($value) || array_is_list($value)) {
            $this->fail('a border object', $path);
        }
        $this->keys($value, ['color', 'width', 'style'], [], $path);
        $this->color($value['color'], $path.'.color');
        $this->unit($value['width'], ['px', 'rem'], $path.'.width', 'dimension');
        $this->strokeStyle($value['style'], $path.'.style');
    }

    private function shadow(mixed $value, string $path): void
    {
        if (!\is_array($value) || [] === $value) {
            $this->fail('a shadow object or non-empty list of shadows', $path);
        }
        $shadows = array_is_list($value) ? $value : [$value];
        foreach ($shadows as $index => $shadow) {
            $itemPath = array_is_list($value) ? $path.'['.$index.']' : $path;
            if (!\is_array($shadow) || array_is_list($shadow)) {
                $this->fail('a shadow object', $itemPath);
            }
            $this->keys($shadow, ['color', 'offsetX', 'offsetY', 'blur', 'spread'], ['inset'], $itemPath);
            $this->color($shadow['color'], $itemPath.'.color');
            foreach (['offsetX', 'offsetY', 'blur', 'spread'] as $field) {
                $this->unit($shadow[$field], ['px', 'rem'], $itemPath.'.'.$field, 'dimension');
            }
            if (\array_key_exists('inset', $shadow) && !\is_bool($shadow['inset'])) {
                $this->fail('a boolean inset value', $itemPath.'.inset');
            }
        }
    }

    private function transition(mixed $value, string $path): void
    {
        if (!\is_array($value) || array_is_list($value)) {
            $this->fail('a transition object', $path);
        }
        $this->keys($value, ['duration', 'delay', 'timingFunction'], [], $path);
        foreach (['duration', 'delay'] as $field) {
            $this->unit($value[$field], ['ms', 's'], $path.'.'.$field, 'duration');
        }
        $this->cubicBezier($value['timingFunction'], $path.'.timingFunction');
    }

    private function typography(mixed $value, string $path): void
    {
        if (!\is_array($value) || array_is_list($value)) {
            $this->fail('a typography object', $path);
        }
        $this->keys($value, ['fontFamily', 'fontSize', 'fontWeight', 'letterSpacing', 'lineHeight'], [], $path);
        $this->fontFamily($value['fontFamily'], $path.'.fontFamily');
        $this->unit($value['fontSize'], ['px', 'rem'], $path.'.fontSize', 'dimension');
        $this->fontWeight($value['fontWeight'], $path.'.fontWeight');
        $this->unit($value['letterSpacing'], ['px', 'rem'], $path.'.letterSpacing', 'dimension');
        $this->number($value['lineHeight'], $path.'.lineHeight');
    }

    /**
     * @param array<mixed> $value
     * @param list<string> $required
     * @param list<string> $optional
     */
    private function keys(array $value, array $required, array $optional, string $path): void
    {
        foreach ($required as $key) {
            if (!\array_key_exists($key, $value)) {
                $this->fail(\sprintf('required property "%s"', $key), $path);
            }
        }
        $unknown = array_diff(array_keys($value), $required, $optional);
        if ([] !== $unknown) {
            $this->fail('no unknown properties ('.implode(', ', $unknown).')', $path);
        }
    }

    private function isNumber(mixed $value): bool
    {
        return (\is_int($value) || \is_float($value)) && (!\is_float($value) || is_finite($value));
    }

    private function isCurlyReference(mixed $value): bool
    {
        return \is_string($value) && 1 === preg_match('/^\{[^{}]+\}$/D', $value);
    }

    private function fail(string $expected, string $path): never
    {
        throw new InvalidArgumentException(\sprintf('Expected %s for DTCG token at "%s".', $expected, $path));
    }
}
