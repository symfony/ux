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
use Symfony\UX\DesignTokens\Token\TokenInterface;
use Symfony\UX\DesignTokens\TokenPath;

/**
 * Turns the resolved design tokens into the `theme.tokens` and `theme.breakpoints` of a Panda config.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class DesignTokensConverter
{
    /**
     * The DTCG path prefixes each token category reads, with the token types it accepts.
     */
    public const CATEGORIES = [
        'colors' => [['color', 'colors'], ['color']],
        'spacing' => [['dimension.spacing', 'dimension.space', 'spacing', 'space'], ['dimension']],
        'sizes' => [['dimension.size', 'dimension.sizes', 'size', 'sizes'], ['dimension']],
        'radii' => [['dimension.radius', 'dimension.radii', 'radius', 'radii', 'rounded'], ['dimension']],
        'fontSizes' => [['font.size', 'font-size', 'fontSize', 'fontSizes'], ['dimension']],
        'fontWeights' => [['font.weight', 'font-weight', 'fontWeight', 'fontWeights'], ['fontWeight', 'number']],
        'lineHeights' => [
            ['font.line-height', 'line.height', 'line-height', 'lineHeight', 'lineHeights', 'leading'],
            ['number', 'dimension'],
        ],
        'fonts' => [['font.family', 'font-family', 'fontFamily', 'fontFamilies', 'fonts'], ['fontFamily']],
        'shadows' => [['shadow', 'shadows', 'elevation'], ['shadow']],
        'zIndex' => [['z-index', 'zIndex'], ['number']],
        'durations' => [['duration', 'durations', 'motion.duration'], ['duration']],
        'easings' => [['easing', 'easings', 'motion.easing'], ['cubicBezier']],
        'breakpoints' => [['breakpoint', 'breakpoints', 'screens'], ['dimension']],
    ];

    public function __construct(
        private readonly ?string $cssPrefix,
    ) {
    }

    /**
     * @param list<array<string, TokenInterface>> $resolutions the tokens of each Resolver permutation, by path
     *
     * @return array{tokens: array<string, mixed>, breakpoints: array<string, string>}
     *
     * @throws InvalidConfigurationException
     */
    public function convert(array $resolutions): array
    {
        $byPath = [];
        foreach ($resolutions as $resolution) {
            foreach ($resolution as $path => $token) {
                $byPath[(string) $path][] = $token;
            }
        }

        $tokens = [];
        $breakpoints = [];
        $names = [];
        foreach ($byPath as $path => $versions) {
            $match = self::match($path);
            if (null === $match) {
                continue;
            }
            [$category, $name] = $match;
            $types = self::CATEGORIES[$category][1];
            if ('' === $name || !\in_array($versions[0]->getType(), $types, true)) {
                continue;
            }
            if (isset($names[$category][$name])) {
                $message = \sprintf(
                    'The "%s" and "%s" design tokens both give the "%s" %s token.',
                    $names[$category][$name],
                    $path,
                    $name,
                    $category,
                );

                throw new InvalidConfigurationException($message);
            }
            $names[$category][$name] = $path;

            if ('breakpoints' === $category) {
                $breakpoints[$name] = self::sameValue($path, $versions);
                continue;
            }
            $definition = [
                'value' => TokenPath::toCssReference($path, null, $this->cssPrefix),
                'extensions' => ['externalVar' => TokenPath::toCssVariable($path, $this->cssPrefix)],
            ];
            self::place($tokens, [$category, ...explode('.', $name)], $definition);
        }

        return ['tokens' => $tokens, 'breakpoints' => $breakpoints];
    }

    /**
     * @return array{string, string}|null the category and the name
     */
    private static function match(string $path): ?array
    {
        $segments = explode('.', $path);
        if ('$root' === end($segments)) {
            array_pop($segments);
        }

        for ($length = \count($segments); $length > 0; --$length) {
            $prefix = implode('.', \array_slice($segments, 0, $length));
            foreach (self::CATEGORIES as $category => [$prefixes]) {
                if (\in_array($prefix, $prefixes, true)) {
                    return [$category, implode('.', \array_slice($segments, $length))];
                }
            }
        }

        return null;
    }

    /**
     * @param list<TokenInterface> $versions
     */
    private static function sameValue(string $path, array $versions): string
    {
        $values = array_values(array_unique(array_map(strval(...), $versions)));
        if (1 === \count($values)) {
            return $values[0];
        }

        $message = \sprintf(
            'The "%s" design token must have the same value in every Resolver context, because a media query cannot change per request.',
            $path,
        );

        throw new InvalidConfigurationException($message);
    }

    /**
     * @param array<string, mixed> $tree
     * @param list<string>         $segments
     * @param array<string, mixed> $definition
     */
    private static function place(array &$tree, array $segments, array $definition): void
    {
        $key = array_shift($segments);
        $existing = $tree[$key] ?? null;
        if ([] === $segments) {
            if (\is_array($existing) && !isset($existing['value'])) {
                $tree[$key]['DEFAULT'] = $definition;
            } else {
                $tree[$key] = $definition;
            }

            return;
        }
        if (\is_array($existing) && isset($existing['value'])) {
            $tree[$key] = ['DEFAULT' => $existing];
        }
        $tree[$key] ??= [];
        self::place($tree[$key], $segments, $definition);
    }
}
