<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Token;

use Symfony\UX\DesignTokens\Exception\InvalidArgumentException;
use Symfony\UX\DesignTokens\Token\Css\CssValue;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class TokenFactory
{
    /**
     * @param array<array-key, mixed> $extensions
     *
     * @throws InvalidArgumentException on an unknown DTCG token type
     */
    public static function create(
        string $type,
        mixed $value,
        ?string $description = null,
        array $extensions = [],
        bool|string|null $deprecated = null,
    ): TokenInterface {
        if ([] !== $extensions) {
            $extensions = array_combine(array_map(strval(...), array_keys($extensions)), $extensions);
        }

        return match ($type) {
            'color' => new ColorToken($value, $description, $extensions, $deprecated),
            'dimension' => new DimensionToken($value, $description, $extensions, $deprecated),
            'number' => new NumberToken($value, $description, $extensions, $deprecated),
            'duration' => new DurationToken($value, $description, $extensions, $deprecated),
            'fontWeight' => new FontWeightToken($value, $description, $extensions, $deprecated),
            'fontFamily' => new FontFamilyToken($value, $description, $extensions, $deprecated),
            'cubicBezier' => new CubicBezierToken($value, $description, $extensions, $deprecated),
            'strokeStyle' => new StrokeStyleToken($value, $description, $extensions, $deprecated),
            'gradient' => new GradientToken(self::stops($value), $description, $extensions, $deprecated),
            'typography' => new TypographyToken($value, $description, $extensions, $deprecated),
            'border' => new BorderToken($value, $description, $extensions, $deprecated),
            'shadow' => new ShadowToken($value, $description, $extensions, $deprecated),
            'transition' => new TransitionToken($value, $description, $extensions, $deprecated),
            default => throw new InvalidArgumentException(\sprintf('Unknown DTCG token type "%s".', $type)),
        };
    }

    /** The declared type of a member decides how it is written, inline or through an alias. */
    public static function project(string $type, mixed $value, string $fallback = ''): string
    {
        if (null === $value) {
            return $fallback;
        }

        try {
            $string = (string) self::create($type, $value);
        } catch (\Throwable) {
            $string = CssValue::stringify($value);
        }

        return '' !== $string ? $string : $fallback;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function stops(mixed $value): array
    {
        if (!\is_array($value) || !array_is_list($value)) {
            throw new InvalidArgumentException('A gradient token value must be a list of color stops.');
        }

        $stops = [];
        foreach ($value as $stop) {
            if (!\is_array($stop)) {
                throw new InvalidArgumentException('A gradient color stop must be an object.');
            }
            $stops[] = array_combine(array_map(strval(...), array_keys($stop)), $stop);
        }

        return $stops;
    }
}
