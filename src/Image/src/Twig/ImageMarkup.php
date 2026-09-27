<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Twig;

use Symfony\UX\Image\Renderer\RenderedImage;
use Symfony\UX\TwigComponent\ComponentAttributes;
use Twig\Extra\Html\HtmlAttr\InlineStyle;

/**
 * Builds the HTML of a rendered image, shared by ux_image() and <twig:ux:image>.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class ImageMarkup
{
    public static function render(RenderedImage $rendered, ComponentAttributes $attributes): string
    {
        $img = '<img'.$attributes->defaults($rendered->imgAttributes).' />';

        if ([] === $rendered->sources) {
            return $img;
        }

        $sizes = $attributes->all()['sizes'] ?? $rendered->imgAttributes['sizes'] ?? null;

        $html = '<picture>';
        foreach ($rendered->sources as $source) {
            $html .= \sprintf('<source type="%s" srcset="%s"', self::escape($source['type']), self::escape($source['srcset']));
            if (null !== $sizes) {
                $html .= \sprintf(' sizes="%s"', self::escape($sizes));
            }
            $html .= ' />';
        }

        return $html.$img.'</picture>';
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>
     */
    public static function normalizeAttributes(array $attributes): array
    {
        $style = $attributes['style'] ?? null;

        if (is_iterable($style)) {
            // ComponentAttributes renders scalars and AttributeValueInterface values, never a raw array.
            $attributes['style'] = new InlineStyle($style);
        } elseif (null !== $style) {
            // InlineStyle refuses a plain string; wrap it as a one-element list, which getValue() treats as a pre-formed CSS chunk.
            $attributes['style'] = new InlineStyle([$style]);
        }

        return $attributes;
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
    }
}
