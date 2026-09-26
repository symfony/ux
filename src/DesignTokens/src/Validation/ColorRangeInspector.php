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

use Symfony\UX\DesignTokens\Token\ColorToken;
use Symfony\UX\DesignTokens\TokenTree;

/**
 * Color 4.2 component ranges are advice: they are reported, not enforced.
 *
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class ColorRangeInspector
{
    /**
     * @param array<array-key, mixed> $resolvedTokens
     *
     * @return list<string> one human-readable line per component out of range
     */
    public function inspect(array $resolvedTokens): array
    {
        $warnings = [];

        foreach (TokenTree::flatten($resolvedTokens) as $path => $token) {
            if (!$token instanceof ColorToken) {
                continue;
            }
            $value = $token->getValue();
            if (!\is_array($value) || !\is_string($space = $value['colorSpace'] ?? null)) {
                continue;
            }
            $components = $value['components'] ?? null;
            if (!\is_array($components)) {
                continue;
            }

            foreach (array_values($components) as $index => $component) {
                if (!\is_int($component) && !\is_float($component)) {
                    continue;
                }
                if (null !== $range = self::outOfRange($space, $index, $component)) {
                    $warnings[] = \sprintf('%s: component %d is %s, outside the usual range %s for "%s".', $path, $index, $component, $range, $space);
                }
            }
        }

        return $warnings;
    }

    /** @return string|null the expected range when the component falls outside it */
    private static function outOfRange(string $space, int $index, int|float $component): ?string
    {
        return match (true) {
            \in_array($space, ['srgb', 'srgb-linear', 'display-p3', 'a98-rgb', 'prophoto-rgb', 'rec2020', 'xyz-d65', 'xyz-d50'], true) => self::between($component, 0, 1),
            \in_array($space, ['hsl', 'hwb'], true) && 0 === $index => null,
            \in_array($space, ['hsl', 'hwb'], true) => self::between($component, 0, 100),
            \in_array($space, ['lab', 'lch'], true) && 0 === $index => self::between($component, 0, 100),
            \in_array($space, ['oklab', 'oklch'], true) && 0 === $index => self::between($component, 0, 1),
            \in_array($space, ['lch', 'oklch'], true) && 1 === $index => $component < 0 ? '[0, ∞)' : null,
            default => null,
        };
    }

    private static function between(int|float $component, int $min, int $max): ?string
    {
        return $component < $min || $component > $max ? \sprintf('[%d, %d]', $min, $max) : null;
    }
}
