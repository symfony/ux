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

/**
 * Turns the `ux_css` conditions and the design tokens into the project part of a Panda config, and rejects what Panda would silently accept.
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

    public const DARK_CONDITION = [
        ':root[data-theme="dark"] &' => '@slot',
        '@media (prefers-color-scheme: dark)' => [':root:not([data-theme="light"]) &' => '@slot'],
    ];

    /**
     * @param array<string, string|list<string>> $conditions
     * @param array<string, string>              $breakpoints
     * @param array<string, mixed>               $tokens
     *
     * @return array{conditions: array<string, mixed>, theme: array<string, mixed>}
     *
     * @throws InvalidConfigurationException
     */
    public static function convert(array $conditions, array $breakpoints, array $tokens): array
    {
        foreach ($conditions as $name => $condition) {
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

        $preset = PandaConfig::create([]);
        $reserved = [];
        foreach ($preset['utilities'] as $property => $utility) {
            $reserved[$property] = true;
            foreach ((array) ($utility['shorthand'] ?? []) as $shorthand) {
                $reserved[$shorthand] = true;
            }
        }
        foreach (array_keys($breakpoints) as $name) {
            if (isset($reserved[$name])) {
                $message = \sprintf('The "%s" breakpoint has the same name as a CSS property or shorthand.', $name);

                throw new InvalidConfigurationException($message);
            }
        }

        return [
            // mirrors the selectors of the stylesheet UX Design Tokens writes for its dark variables
            'conditions' => ['dark' => self::DARK_CONDITION, ...$conditions],
            'theme' => array_filter([
                'tokens' => $tokens,
                'breakpoints' => $breakpoints,
            ]),
        ];
    }
}
