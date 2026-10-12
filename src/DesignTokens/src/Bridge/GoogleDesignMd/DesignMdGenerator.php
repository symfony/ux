<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Bridge\GoogleDesignMd;

use Symfony\Component\Yaml\Yaml;
use Symfony\UX\DesignTokens\Exception\InvalidArgumentException;
use Symfony\UX\DesignTokens\Generator\GeneratorInterface;
use Symfony\UX\DesignTokens\Token\ColorToken;
use Symfony\UX\DesignTokens\Token\Css\CssValue;
use Symfony\UX\DesignTokens\Token\TokenFactory;
use Symfony\UX\DesignTokens\Token\TokenInterface;
use Symfony\UX\DesignTokens\Token\TypographyToken;

/**
 * @see https://github.com/google-labs-code/design.md
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class DesignMdGenerator implements GeneratorInterface
{
    private const array CSS_FUNCTION_SPACES = ['hsl', 'hwb', 'lab', 'lch', 'oklab', 'oklch'];

    private const array GROUP_SEGMENTS = ['color', 'colors', 'colour', 'colours', 'spacing', 'space', 'dimension', 'dimensions', 'size', 'sizes', 'radius', 'radii', 'rounded', 'typography', 'type', 'text', 'font', 'fonts'];

    /**
     * @param array<array-key, mixed> $resolvedTokens
     * @param array<string, mixed>    $context        {@see GeneratorInterface::TITLE} names the design system
     */
    public function generate(array $resolvedTokens, array $context = []): string
    {
        $title = $context[self::TITLE] ?? 'Design System';
        if (!\is_string($title)) {
            throw new InvalidArgumentException('The DESIGN.md title must be a string.');
        }

        $entries = [];
        foreach ($this->flatten($resolvedTokens) as $path => $token) {
            $entries[] = ['path' => $path, 'token' => $token];
        }
        $frontMatter = $this->frontMatter($entries, $title);
        $lines = [
            '---',
            trim(Yaml::dump($frontMatter, 8, 2)),
            '---',
            '',
            '# '.$title,
            '',
            '## Overview',
            '',
            'The YAML front matter holds the normative values of this design system. The sections below describe how to apply them.',
            '',
        ];

        $grouped = $this->groupByType(array_values(array_filter($entries, fn (array $entry): bool => !$this->isComponentPath($entry['path']))));
        $this->renderSection($lines, 'Colors', 'The color tokens are listed under `colors` in the front matter.', $grouped['color'] ?? []);
        $this->renderSection($lines, 'Typography', 'The typography tokens are listed under `typography` in the front matter.', $grouped['typography'] ?? []);
        $this->renderSection($lines, 'Layout', 'The spacing tokens are listed under `spacing` in the front matter.', array_values(array_filter($grouped['dimension'] ?? [], fn (array $entry): bool => !$this->isRoundedPath($entry['path']))));
        $this->renderElevation($lines, $grouped['shadow'] ?? []);
        $this->renderSection($lines, 'Shapes', 'The rounded corner tokens are listed under `rounded` in the front matter.', array_values(array_filter($grouped['dimension'] ?? [], fn (array $entry): bool => $this->isRoundedPath($entry['path']))));
        $this->renderSection($lines, 'Components', 'The component tokens are listed under `components` in the front matter.', array_values(array_filter($entries, fn (array $entry): bool => $this->isComponentPath($entry['path']))));
        $this->renderAdditionalTokens($lines, $entries);

        $lines[] = '## Do\'s and Don\'ts';
        $lines[] = '';
        $lines[] = '- Do use the tokens in the YAML front matter as the normative values.';
        $lines[] = '- Don\'t introduce a new value when an existing token expresses the same intent.';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * @param array<int, array{path: string, token: TokenInterface}> $entries
     *
     * @return array<string, mixed>
     */
    private function frontMatter(array $entries, string $title): array
    {
        /** @var array<string, string> $colors */
        $colors = [];
        /** @var array<string, array<string, string>> $typography */
        $typography = [];
        /** @var array<string, string> $spacing */
        $spacing = [];
        /** @var array<string, string> $rounded */
        $rounded = [];
        /** @var array<string, array<string, string>> $components */
        $components = [];
        /** @var array<string, array<string, string>> $usedNames */
        $usedNames = [
            'colors' => [],
            'typography' => [],
            'spacing' => [],
            'rounded' => [],
        ];

        foreach ($entries as ['path' => $path, 'token' => $token]) {
            if ($this->isComponentPath($path)) {
                $parts = explode('.', $path);
                $component = $parts[1] ?? 'component';
                $property = implode('.', \array_slice($parts, 2)) ?: 'value';
                $components[$component][$property] = $this->componentValue($token, $path);

                continue;
            }

            match ($token->getType()) {
                'color' => $colors[$this->tokenName($path, $usedNames['colors'])] = $this->color($token, $path),
                'typography' => $typography[$this->tokenName($path, $usedNames['typography'])] = $this->typographyValue($token),
                'dimension' => $this->isRoundedPath($path)
                    ? $rounded[$this->tokenName($path, $usedNames['rounded'])] = CssValue::stringify($token->getValue())
                    : $spacing[$this->tokenName($path, $usedNames['spacing'])] = CssValue::stringify($token->getValue()),
                default => null,
            };
        }

        $frontMatter = ['version' => 'alpha', 'name' => $title];
        foreach (['colors' => $colors, 'typography' => $typography, 'spacing' => $spacing, 'rounded' => $rounded, 'components' => $components] as $key => $values) {
            if ([] !== $values) {
                $frontMatter[$key] = $values;
            }
        }

        return $frontMatter;
    }

    /**
     * @param array<int, array{path: string, token: TokenInterface}> $entries
     *
     * @return array<string, array<int, array{path: string, token: TokenInterface}>>
     */
    private function groupByType(array $entries): array
    {
        $grouped = [];

        foreach ($entries as $entry) {
            $grouped[$entry['token']->getType()][] = $entry;
        }

        return $grouped;
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return iterable<string, TokenInterface>
     */
    private function flatten(array $data, string $prefix = ''): iterable
    {
        foreach ($data as $key => $value) {
            $name = '' !== $prefix ? "{$prefix}.{$key}" : (string) $key;

            if (\is_array($value)) {
                yield from $this->flatten($value, $name);
            } elseif ($value instanceof TokenInterface) {
                yield $name => $value;
            }
        }
    }

    /**
     * @param list<string>                                           $lines
     * @param array<int, array{path: string, token: TokenInterface}> $entries
     */
    private function renderSection(array &$lines, string $heading, string $text, array $entries): void
    {
        if ([] === $entries) {
            return;
        }

        $lines[] = '## '.$heading;
        $lines[] = '';
        $lines[] = $text;
        $lines[] = '';
    }

    /**
     * @param list<string>                                           $lines
     * @param array<int, array{path: string, token: TokenInterface}> $entries
     */
    private function renderElevation(array &$lines, array $entries): void
    {
        if ([] === $entries) {
            return;
        }

        $lines[] = '## Elevation & Depth';
        $lines[] = '';
        $lines[] = 'The shadow tokens describe the system\'s elevation and depth.';
        $lines[] = '';
        $this->renderTable($lines, 'Token', 'Value', 'Description', $entries);
        $lines[] = '';
    }

    /**
     * @param list<string>                                           $lines
     * @param array<int, array{path: string, token: TokenInterface}> $entries
     */
    private function renderAdditionalTokens(array &$lines, array $entries): void
    {
        $entries = array_values(array_filter($entries, fn (array $entry): bool => !$this->isGoogleToken($entry['path'], $entry['token'])));
        if ([] === $entries) {
            return;
        }

        $lines[] = '## Additional Tokens';
        $lines[] = '';
        $lines[] = 'These tokens are preserved for consumers that support additional DTCG types.';
        $lines[] = '';
        $this->renderTable($lines, 'Token', 'Type', 'Value', $entries);
        $lines[] = '';
    }

    /**
     * @param list<string>                                           $lines
     * @param array<int, array{path: string, token: TokenInterface}> $entries
     */
    private function renderTable(array &$lines, string $first, string $second, string $third, array $entries): void
    {
        $lines[] = "| {$first} | {$second} | {$third} |";
        $lines[] = '|-------|-------|-------------|';

        foreach ($entries as ['path' => $path, 'token' => $token]) {
            $lines[] = \sprintf(
                '| `%s` | `%s` | %s |',
                $this->escape($path),
                $this->escape('Type' === $second ? $token->getType() : (string) $token),
                $this->escape('Type' === $second ? (string) $token : ($token->getDescription() ?? '')),
            );
        }
    }

    /** @return array<string, string> */
    private function typographyValue(TokenInterface $token): array
    {
        \assert($token instanceof TypographyToken);
        $value = $token->getValue();
        $result = [];

        // fontFeature and fontVariation have no DTCG type of their own.
        $types = [
            'fontFamily' => 'fontFamily', 'fontSize' => 'dimension', 'fontWeight' => 'fontWeight',
            'lineHeight' => 'number', 'letterSpacing' => 'dimension',
        ];

        foreach (['fontFamily', 'fontSize', 'fontWeight', 'lineHeight', 'letterSpacing', 'fontFeature', 'fontVariation'] as $property) {
            if (\array_key_exists($property, $value)) {
                $result[$property] = isset($types[$property])
                    ? TokenFactory::project($types[$property], $value[$property])
                    : CssValue::stringify($value[$property]);
            }
        }

        return $result;
    }

    private function componentValue(TokenInterface $token, string $path): string
    {
        return $token instanceof ColorToken ? $this->color($token, $path) : (string) $token;
    }

    /** DESIGN.md colors exclude the CSS color() function. */
    private function color(TokenInterface $token, string $path): string
    {
        $value = $token->getValue();
        if (!\is_array($value) || !\is_string($space = $value['colorSpace'] ?? null)) {
            return (string) $token;
        }

        if ('srgb' === $space && \is_array($components = $value['components'] ?? null) && array_is_list($components)) {
            $channels = array_map(static fn (mixed $component): string => is_numeric($component) ? round(100 * (float) $component, 4).'%' : 'none', $components);
            $alpha = $value['alpha'] ?? 1;

            return \sprintf('rgb(%s%s)', implode(' ', $channels), is_numeric($alpha) && $alpha < 1 ? ' / '.$alpha : '');
        }

        if (\in_array($space, self::CSS_FUNCTION_SPACES, true)) {
            return (string) $token;
        }

        if (\is_string($value['hex'] ?? null)) {
            return $value['hex'];
        }

        throw new InvalidArgumentException(\sprintf('Design token "%s" uses the %s color space, which DESIGN.md cannot express. Add a "hex" fallback.', $path, $space));
    }

    /** @param array<string, string> $usedNames name to the path that took it */
    private function tokenName(string $path, array &$usedNames): string
    {
        $parts = explode('.', $path);
        if (\count($parts) > 1 && \in_array(strtolower($parts[0]), self::GROUP_SEGMENTS, true)) {
            array_shift($parts);
        }
        $name = implode('-', $parts);

        if (isset($usedNames[$name])) {
            throw new InvalidArgumentException(\sprintf('Design tokens "%s" and "%s" both export to the DESIGN.md name "%s". Rename one of them.', $usedNames[$name], $path, $name));
        }

        $usedNames[$name] = $path;

        return $name;
    }

    private function isRoundedPath(string $path): bool
    {
        return 1 === preg_match('/(^|\.)((border-)?radius|rounded|corner)(\.|$)/i', $path);
    }

    private function isComponentPath(string $path): bool
    {
        return 1 === preg_match('/^(components?|ui)\./i', $path);
    }

    private function isGoogleToken(string $path, TokenInterface $token): bool
    {
        return $this->isComponentPath($path)
            || \in_array($token->getType(), ['color', 'typography', 'dimension', 'shadow'], true);
    }

    private function escape(string $value): string
    {
        return str_replace('|', '\\|', $value);
    }
}
