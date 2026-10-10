<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Generator;

use Symfony\UX\DesignTokens\Exception\InvalidArgumentException;
use Symfony\UX\DesignTokens\Token\ShadowToken;
use Symfony\UX\DesignTokens\Token\TokenFactory;
use Symfony\UX\DesignTokens\Token\TokenInterface;
use Symfony\UX\DesignTokens\TokenPath;
use Symfony\UX\DesignTokens\TokenTree;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class CssGenerator implements GeneratorInterface
{
    /** @var array<string, array<string, array{string, string}>> */
    private const MEMBERS = [
        'typography' => [
            'fontFamily' => ['font-family', 'fontFamily'],
            'fontSize' => ['font-size', 'dimension'],
            'fontWeight' => ['font-weight', 'fontWeight'],
            'letterSpacing' => ['letter-spacing', 'dimension'],
            'lineHeight' => ['line-height', 'number'],
        ],
        'border' => [
            'color' => ['color', 'color'],
            'width' => ['width', 'dimension'],
            'style' => ['style', 'strokeStyle'],
        ],
        'transition' => [
            'duration' => ['duration', 'duration'],
            'timingFunction' => ['timing-function', 'cubicBezier'],
            'delay' => ['delay', 'duration'],
        ],
        'shadow' => [
            'color' => ['color', 'color'],
            'offsetX' => ['offset-x', 'dimension'],
            'offsetY' => ['offset-y', 'dimension'],
            'blur' => ['blur', 'dimension'],
            'spread' => ['spread', 'dimension'],
        ],
    ];

    public function __construct(private readonly ?string $prefix = null)
    {
        if (null !== $prefix) {
            TokenPath::validateCssPrefix($prefix);
        }
    }

    /**
     * @param array<array-key, mixed> $resolvedTokens nested token tree from {@see TokenTreeBuilder}, written to :root
     * @param array<string, mixed>    $context        {@see GeneratorInterface::CSS_PREFIX} replaces the configured prefix
     */
    public function generate(array $resolvedTokens, array $context = []): string
    {
        $prefix = $context[GeneratorInterface::CSS_PREFIX] ?? $this->prefix;
        if (null !== $prefix) {
            if (!\is_string($prefix)) {
                throw new InvalidArgumentException('The CSS prefix must be a string.');
            }
            TokenPath::validateCssPrefix($prefix);
        }

        $atProperties = [];
        $rootVars = [':root {'];
        $light = $this->variables($resolvedTokens, $prefix);

        foreach ($light as $cssVarName => [$cssValue, $subValue]) {
            $syntax = $this->detectSyntax($subValue);

            if (null !== $syntax) {
                $atProperties[] = \sprintf(
                    "@property %s { syntax: '%s'; inherits: true; initial-value: %s; }",
                    $cssVarName,
                    $syntax,
                    $cssValue,
                );
            }

            $rootVars[] = \sprintf('  %s: %s;', $cssVarName, $cssValue);
        }

        $rootVars[] = '}';
        $css = implode("\n", $atProperties)."\n\n".implode("\n", $rootVars);

        $darkTokens = $context[GeneratorInterface::DARK_TOKENS] ?? null;
        if (!\is_array($darkTokens)) {
            return $css;
        }

        $dark = [];
        foreach ($this->variables($darkTokens, $prefix) as $cssVarName => [$cssValue]) {
            if (($light[$cssVarName][0] ?? null) !== $cssValue) {
                $dark[] = \sprintf('%s: %s;', $cssVarName, $cssValue);
            }
        }
        if ([] === $dark) {
            return $css;
        }

        return $css."\n\n"
            ."@media (prefers-color-scheme: dark) {\n"
            .'  :root:not([data-theme="light"]):not([data-theme="dark"]) {'."\n    ".implode("\n    ", $dark)."\n  }\n}\n\n"
            .':root[data-theme="dark"] {'."\n  ".implode("\n  ", $dark)."\n}";
    }

    /**
     * @param array<array-key, mixed> $tokens
     *
     * @return array<string, array{string, TokenInterface|string}>
     */
    private function variables(array $tokens, ?string $prefix): array
    {
        $variables = [];
        $generatedNames = [];

        foreach (TokenTree::flatten($tokens) as $name => $token) {
            foreach ($this->unrollToken($name, $token) as $subName => $subValue) {
                $cssVarName = TokenPath::toCssVariable($subName, $prefix);
                if (isset($generatedNames[$cssVarName]) && $generatedNames[$cssVarName] !== $subName) {
                    throw new InvalidArgumentException(\sprintf('Design token paths "%s" and "%s" generate the same CSS custom property "%s".', $generatedNames[$cssVarName], $subName, $cssVarName));
                }
                $generatedNames[$cssVarName] = $subName;
                $variables[$cssVarName] = [(string) $subValue, $subValue];
            }
        }

        return $variables;
    }

    /** @return iterable<string, TokenInterface|string> */
    private function unrollToken(string $name, TokenInterface $token): iterable
    {
        yield $name => $token;

        if ($token instanceof ShadowToken) {
            yield from self::unrollShadow($name, $token);

            return;
        }

        $members = self::MEMBERS[$token->getType()] ?? null;
        if (null === $members) {
            return;
        }

        $value = $token->getValue();
        if (!\is_array($value)) {
            return;
        }

        foreach ($members as $key => [$suffix, $type]) {
            if (isset($value[$key])) {
                yield "$name-$suffix" => TokenFactory::project($type, $value[$key]);
            }
        }
    }

    /** @return iterable<string, string> */
    private static function unrollShadow(string $name, ShadowToken $token): iterable
    {
        $value = $token->getValue();
        $layers = isset($value['color']) ? [$value] : array_values($value);

        foreach ($layers as $index => $layer) {
            if (!\is_array($layer)) {
                continue;
            }
            $prefix = 1 === \count($layers) ? $name : $name.'-'.($index + 1);

            foreach (self::MEMBERS['shadow'] as $key => [$suffix, $type]) {
                if (isset($layer[$key])) {
                    yield "$prefix-$suffix" => TokenFactory::project($type, $layer[$key]);
                }
            }
        }
    }

    private function detectSyntax(mixed $value): ?string
    {
        if ($value instanceof TokenInterface) {
            return match ($value->getType()) {
                'color' => '<color>',
                'dimension' => \is_array($dimension = $value->getValue()) && 'px' === ($dimension['unit'] ?? null) ? '<length>' : null,
                'duration' => '<time>',
                'number' => '<number>',
                default => null,
            };
        }

        if (!\is_scalar($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return '<number>';
        }

        $str = (string) $value;

        // An initial-value must not depend on the font size, so rem and em get no rule.
        if (preg_match('/^[+-]?(?:\d+(?:\.\d+)?|\.\d+)(px|vh|vw|vmin|vmax|cm|mm|in|pt|pc)$/', $str)) {
            return '<length>';
        }

        if (preg_match('/^[+-]?(?:\d+(?:\.\d+)?|\.\d+)%$/', $str)) {
            return '<percentage>';
        }

        return null;
    }
}
