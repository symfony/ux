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

use Symfony\UX\Css\Engine\Preset\BaseTransforms;

/**
 * The build path of a Panda config, each part created on first use.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class Engine
{
    private ?Breakpoints $breakpoints = null;
    private ?Tokens $tokens = null;
    private ?Utilities $utilities = null;
    private ?Conditions $conditions = null;
    private ?StyleEncoder $encoder = null;
    private ?StyleDecoder $decoder = null;
    private ?Stylesheet $stylesheet = null;

    /**
     * @param array<string, mixed>                                                                      $config     a Panda config
     * @param array<string, \Closure(string|int|float|bool, TransformArgs): ?array<string, mixed>>|null $transforms the transforms of the utilities, the base preset ones by default
     */
    public function __construct(
        private readonly array $config,
        private readonly ?array $transforms = null,
    ) {
    }

    /**
     * @param array<string, mixed> $project the project part of a Panda config, see {@see PandaConfig::create()}
     */
    public static function fromProjectConfig(array $project): self
    {
        return new self(PandaConfig::create($project));
    }

    /**
     * @return array<string, mixed>
     */
    public function config(): array
    {
        return $this->config;
    }

    public function breakpoints(): Breakpoints
    {
        return $this->breakpoints ??= new Breakpoints($this->config['theme']['breakpoints'] ?? []);
    }

    public function tokens(): Tokens
    {
        if (null !== $this->tokens) {
            return $this->tokens;
        }

        $theme = $this->config['theme'] ?? [];
        $prefix = $this->config['prefix'] ?? '';
        $hash = $this->config['hash'] ?? false;

        return $this->tokens = new Tokens(
            $theme['tokens'] ?? [],
            $theme['semanticTokens'] ?? [],
            $theme['breakpoints'] ?? [],
            (string) (\is_array($prefix) ? $prefix['cssVar'] ?? '' : $prefix),
            (bool) (\is_array($hash) ? $hash['cssVar'] ?? false : $hash),
            $theme['colorPalette'] ?? [],
        );
    }

    public function utilities(): Utilities
    {
        return $this->utilities ??= new Utilities(
            $this->config['utilities'] ?? [],
            $this->config['separator'] ?? '_',
            $this->tokens(),
            $this->transforms ?? BaseTransforms::all(),
        );
    }

    public function conditions(): Conditions
    {
        return $this->conditions ??= new Conditions(
            $this->config['conditions'] ?? [],
            $this->breakpoints(),
            $this->config['theme']['containerSizes'] ?? [],
            $this->config['theme']['containerNames'] ?? [],
            $this->config['themes'] ?? [],
        );
    }

    public function encoder(): StyleEncoder
    {
        return $this->encoder ??= new StyleEncoder($this->utilities(), $this->conditions());
    }

    public function decoder(): StyleDecoder
    {
        $prefix = $this->config['prefix'] ?? '';

        return $this->decoder ??= new StyleDecoder(
            $this->utilities(),
            $this->conditions(),
            (string) (\is_array($prefix) ? $prefix['className'] ?? '' : $prefix),
        );
    }

    /**
     * The classes of a style hash, named like the selectors of the CSS this engine writes.
     *
     * @param array<array-key, mixed> $styles
     */
    public function classNames(array $styles): string
    {
        $classes = [];
        foreach ($this->decoder()->decode($this->encoder()->encode($styles)) as $style) {
            $classes[$style->htmlClass] = true;
        }

        return implode(' ', array_keys($classes));
    }

    public function tokenCss(): TokenCss
    {
        return new TokenCss(
            $this->tokens(),
            $this->conditions(),
            $this->config['cssVarRoot'] ?? ':where(:root, :host)',
        );
    }

    public function stylesheet(): Stylesheet
    {
        return $this->stylesheet ??= new Stylesheet($this->breakpoints());
    }
}
