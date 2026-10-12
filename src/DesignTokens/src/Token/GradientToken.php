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

use Symfony\UX\DesignTokens\Token\Css\CssValue;

/**
 * @extends AbstractToken<list<array<string, mixed>>>
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class GradientToken extends AbstractToken
{
    public function getType(): string
    {
        return 'gradient';
    }

    /**
     * Positions outside [0, 1] are clamped, as DTCG 2025.10 requires.
     *
     * @param list<array<string, mixed>> $value
     * @param array<string, mixed>       $extensions
     */
    public function __construct(
        array $value,
        ?string $description = null,
        array $extensions = [],
        bool|string|null $deprecated = null,
    ) {
        foreach ($value as $index => $stop) {
            $position = $stop['position'] ?? null;
            if (\is_int($position) || \is_float($position)) {
                $value[$index]['position'] = min(1, max(0, $position));
            }
        }

        parent::__construct($value, $description, $extensions, $deprecated);
    }

    public function __toString(): string
    {
        /** @var list<array<string, mixed>> $value */
        $value = $this->value;
        $stops = array_map(static fn (array $stop): string => \sprintf(
            '%s %s',
            CssValue::stringify($stop['color'] ?? null),
            self::position($stop['position'] ?? null),
        ), $value);

        return 'linear-gradient('.implode(', ', $stops).')';
    }

    /** CSS wants a percentage: a bare number is not a valid stop position. */
    private static function position(mixed $position): string
    {
        if (!\is_int($position) && !\is_float($position)) {
            return CssValue::stringify($position);
        }

        $percentage = rtrim(rtrim(number_format($position * 100, 4, '.', ''), '0'), '.');

        return ('' === $percentage ? '0' : $percentage).'%';
    }
}
