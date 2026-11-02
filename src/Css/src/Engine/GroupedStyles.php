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

use Symfony\UX\Css\Engine\Css\Node;

/**
 * Port of `serializeStyles()` (packages/core/src/serialize.ts): style objects keyed by selector, not by class.
 *
 * Values go through the same utilities and tokens as the atomic styles, and each selector keeps its own rule.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class GroupedStyles
{
    private const CSS_TYPES = __DIR__.'/../../resources/css-types.json';

    /**
     * @var array<string, true>|null
     */
    private static ?array $properties = null;

    public function __construct(
        private readonly Utilities $utilities,
        private readonly Conditions $conditions,
        private readonly StyleEncoder $encoder,
        private readonly StyleDecoder $decoder,
    ) {
    }

    /**
     * @param array<string, array<array-key, mixed>> $styles style objects by selector; a nested object whose key is neither a property nor a condition is a selector
     *
     * @return list<Node>
     */
    public function serialize(array $styles): array
    {
        $result = [];
        foreach ($styles as $scope => $style) {
            $entries = $this->encoder->encode($this->rewriteSelectors($style));
            $result[$scope] = $this->decoder->group($entries);
        }

        return StyleStringifier::stringify($result);
    }

    /**
     * Rewrites a selector like "body, :root" to "& body, & :root", so that it is parsed as a nested selector.
     *
     * @param array<array-key, mixed> $style
     *
     * @return array<array-key, mixed>
     */
    private function rewriteSelectors(array $style): array
    {
        $rewritten = [];
        foreach ($style as $key => $value) {
            $key = (string) $key;
            if (\is_array($value)) {
                if (!$this->conditions->isCondition($key) && !$this->isProperty($key)) {
                    $key = implode(', ', array_map(static fn (string $selector): string => '& '.$selector, Selectors::parse($key)));
                }
                $value = $this->rewriteSelectors($value);
            }
            $rewritten[$key] = $value;
        }

        return $rewritten;
    }

    private function isProperty(string $key): bool
    {
        if (null !== $this->utilities->get($key)) {
            return true;
        }

        self::$properties ??= array_fill_keys(
            json_decode(file_get_contents(self::CSS_TYPES), true, flags: \JSON_THROW_ON_ERROR)['properties'],
            true,
        );

        return isset(self::$properties[$key]);
    }
}
