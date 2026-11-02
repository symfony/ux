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
 * Port of `generateResetCss()` (packages/generator/src/artifacts/css/reset-css.ts): the content of the reset layer.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class ResetCss
{
    private const FONT_FALLBACK = "ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji'";

    private const FONT_MONO_FALLBACK = "ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New'";

    /**
     * @param bool|array{scope?: string, level?: string} $preflight Panda's `preflight` option
     */
    public function __construct(
        private readonly GroupedStyles $styles,
        private readonly bool|array $preflight = true,
    ) {
    }

    /**
     * @return list<Node>
     */
    public function nodes(): array
    {
        $options = \is_array($this->preflight) ? $this->preflight : [];
        $scope = $options['scope'] ?? '';
        $level = $options['level'] ?? 'parent';

        $selector = '';
        if ('' !== $scope && 'parent' === $level) {
            $selector = $scope.' ';
        } elseif ('' !== $scope && 'element' === $level) {
            $selector = '&'.$scope;
        }

        $reset = [
            '' !== $scope ? $scope : 'html, :host' => [
                'lineHeight' => '1.5',
                '--font-fallback' => self::FONT_FALLBACK,
                'WebkitTextSizeAdjust' => '100%',
                'WebkitFontSmoothing' => 'antialiased',
                'MozOsxFontSmoothing' => 'grayscale',
                'MozTabSize' => '[4]',
                'tabSize' => '[4]',
                'fontFamily' => 'var(--global-font-body, var(--font-fallback))',
                'WebkitTapHighlightColor' => 'transparent',
            ],
        ];

        $scoped = self::scoped();
        if ('element' === $level) {
            foreach ($scoped as $key => $style) {
                [$trailingPseudo, $rebuiltSelector] = Selectors::extractTrailingPseudos((string) $key);
                if (null === $trailingPseudo) {
                    $reset[$key] = [$selector => $style];
                    continue;
                }

                // "::selection.pd-reset" is not a selector: the scope goes before the pseudo-element
                $reset[$scope.' '.$rebuiltSelector.$trailingPseudo] = $style;
            }
        } elseif ('' !== $selector) {
            $reset[$selector] = $scoped;
        } else {
            $reset = [...$reset, ...$scoped];
        }

        return $this->styles->serialize($reset);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function scoped(): array
    {
        return [
            '*, ::before, ::after, ::backdrop, ::file-selector-button' => [
                'margin' => '0px',
                'padding' => '0px',
                'boxSizing' => 'border-box',
                'borderWidth' => '0px',
                'borderStyle' => 'solid',
                'borderColor' => 'var(--global-color-border, currentcolor)',
            ],
            'hr' => [
                'height' => '0px',
                'color' => 'inherit',
                'borderTopWidth' => '1px',
            ],
            'body' => [
                'height' => '100%',
                'lineHeight' => 'inherit',
            ],
            'img' => [
                'borderStyle' => 'none',
            ],
            'img, svg, video, canvas, audio, iframe, embed, object' => [
                'display' => 'block',
                'verticalAlign' => 'middle',
            ],
            'img, video' => [
                'maxWidth' => '100%',
                'height' => 'auto',
            ],
            'h1, h2, h3, h4, h5, h6' => [
                'fontSize' => 'inherit',
                'fontWeight' => 'inherit',
                'textWrap' => 'balance',
            ],
            'p, h1, h2, h3, h4, h5, h6' => [
                'overflowWrap' => 'break-word',
            ],
            'ol, ul, menu' => [
                'listStyle' => 'none',
            ],
            "button, input:where([type='button'], [type='reset'], [type='submit']), ::file-selector-button" => [
                'appearance' => 'button',
            ],
            'button, input, optgroup, select, textarea, ::file-selector-button' => [
                'font' => 'inherit',
                'fontFeatureSettings' => 'inherit',
                'fontVariationSettings' => 'inherit',
                'letterSpacing' => 'inherit',
                'color' => 'inherit',
                'background' => 'transparent',
            ],
            '::placeholder' => [
                'opacity' => 1,
                '--placeholder-fallback' => 'rgba(0, 0, 0, 0.5)',
                'color' => 'var(--global-color-placeholder, var(--placeholder-fallback))',
                // Safari issues with color-mix(...), see https://github.com/chakra-ui/panda/issues/3194
                '@supports (not (-webkit-appearance: -apple-pay-button)) or (contain-intrinsic-size: 1px)' => [
                    '--placeholder-fallback' => 'color-mix(in oklab, currentcolor 50%, transparent)',
                ],
            ],
            '::selection' => [
                'backgroundColor' => 'var(--global-color-selection, rgba(0, 115, 255, 0.3))',
            ],
            'textarea' => [
                'resize' => 'vertical',
            ],
            'table' => [
                'textIndent' => '0px',
                'borderColor' => 'inherit',
                'borderCollapse' => 'collapse',
            ],
            'summary' => [
                'display' => 'list-item',
            ],
            'small' => [
                'fontSize' => '80%',
            ],
            'sub, sup' => [
                'fontSize' => '75%',
                'lineHeight' => 0,
                'position' => 'relative',
                'verticalAlign' => 'baseline',
            ],
            'sub' => [
                'bottom' => '-0.25em',
            ],
            'sup' => [
                'top' => '-0.5em',
            ],
            'dialog' => [
                'padding' => '0px',
            ],
            'a' => [
                'color' => 'inherit',
                'textDecoration' => 'inherit',
            ],
            'abbr:where([title])' => [
                'textDecoration' => 'underline dotted',
            ],
            'b, strong' => [
                'fontWeight' => 'bolder',
            ],
            'code, kbd, samp, pre' => [
                '--font-mono-fallback' => self::FONT_MONO_FALLBACK,
                'fontFamily' => 'var(--global-font-mono, var(--font-mono-fallback))',
                'fontSize' => '1em',
                'fontFeatureSettings' => 'normal',
                'fontVariationSettings' => 'normal',
            ],
            'progress' => [
                'verticalAlign' => 'baseline',
            ],
            '::-webkit-search-decoration, ::-webkit-search-cancel-button' => [
                'WebkitAppearance' => 'none',
            ],
            '::-webkit-inner-spin-button, ::-webkit-outer-spin-button' => [
                'height' => 'auto',
            ],
            ':-moz-ui-invalid' => [
                'boxShadow' => 'none',
            ],
            ':-moz-focusring' => [
                'outline' => 'auto',
            ],
            "[hidden]:where(:not([hidden='until-found']))" => [
                'display' => 'none !important',
            ],
        ];
    }
}
