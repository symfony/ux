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

/**
 * Port of `svgToDataUri()` (packages/token-dictionary/src/mini-svg-uri.ts).
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class SvgDataUri
{
    private const COLOR_NAMES = [
        'aqua' => '/#00ffff(ff)?(?!\w)|#0ff(f)?(?!\w)/i',
        'azure' => '/#f0ffff(ff)?(?!\w)/i',
        'beige' => '/#f5f5dc(ff)?(?!\w)/i',
        'bisque' => '/#ffe4c4(ff)?(?!\w)/i',
        'black' => '/#000000(ff)?(?!\w)|#000(f)?(?!\w)/i',
        'blue' => '/#0000ff(ff)?(?!\w)|#00f(f)?(?!\w)/i',
        'brown' => '/#a52a2a(ff)?(?!\w)/i',
        'coral' => '/#ff7f50(ff)?(?!\w)/i',
        'cornsilk' => '/#fff8dc(ff)?(?!\w)/i',
        'crimson' => '/#dc143c(ff)?(?!\w)/i',
        'cyan' => '/#00ffff(ff)?(?!\w)|#0ff(f)?(?!\w)/i',
        'darkblue' => '/#00008b(ff)?(?!\w)/i',
        'darkcyan' => '/#008b8b(ff)?(?!\w)/i',
        'darkgrey' => '/#a9a9a9(ff)?(?!\w)/i',
        'darkred' => '/#8b0000(ff)?(?!\w)/i',
        'deeppink' => '/#ff1493(ff)?(?!\w)/i',
        'dimgrey' => '/#696969(ff)?(?!\w)/i',
        'gold' => '/#ffd700(ff)?(?!\w)/i',
        'green' => '/#008000(ff)?(?!\w)/i',
        'grey' => '/#808080(ff)?(?!\w)/i',
        'honeydew' => '/#f0fff0(ff)?(?!\w)/i',
        'hotpink' => '/#ff69b4(ff)?(?!\w)/i',
        'indigo' => '/#4b0082(ff)?(?!\w)/i',
        'ivory' => '/#fffff0(ff)?(?!\w)/i',
        'khaki' => '/#f0e68c(ff)?(?!\w)/i',
        'lavender' => '/#e6e6fa(ff)?(?!\w)/i',
        'lime' => '/#00ff00(ff)?(?!\w)|#0f0(f)?(?!\w)/i',
        'linen' => '/#faf0e6(ff)?(?!\w)/i',
        'maroon' => '/#800000(ff)?(?!\w)/i',
        'moccasin' => '/#ffe4b5(ff)?(?!\w)/i',
        'navy' => '/#000080(ff)?(?!\w)/i',
        'oldlace' => '/#fdf5e6(ff)?(?!\w)/i',
        'olive' => '/#808000(ff)?(?!\w)/i',
        'orange' => '/#ffa500(ff)?(?!\w)/i',
        'orchid' => '/#da70d6(ff)?(?!\w)/i',
        'peru' => '/#cd853f(ff)?(?!\w)/i',
        'pink' => '/#ffc0cb(ff)?(?!\w)/i',
        'plum' => '/#dda0dd(ff)?(?!\w)/i',
        'purple' => '/#800080(ff)?(?!\w)/i',
        'red' => '/#ff0000(ff)?(?!\w)|#f00(f)?(?!\w)/i',
        'salmon' => '/#fa8072(ff)?(?!\w)/i',
        'seagreen' => '/#2e8b57(ff)?(?!\w)/i',
        'seashell' => '/#fff5ee(ff)?(?!\w)/i',
        'sienna' => '/#a0522d(ff)?(?!\w)/i',
        'silver' => '/#c0c0c0(ff)?(?!\w)/i',
        'skyblue' => '/#87ceeb(ff)?(?!\w)/i',
        'snow' => '/#fffafa(ff)?(?!\w)/i',
        'tan' => '/#d2b48c(ff)?(?!\w)/i',
        'teal' => '/#008080(ff)?(?!\w)/i',
        'thistle' => '/#d8bfd8(ff)?(?!\w)/i',
        'tomato' => '/#ff6347(ff)?(?!\w)/i',
        'violet' => '/#ee82ee(ff)?(?!\w)/i',
        'wheat' => '/#f5deb3(ff)?(?!\w)/i',
        'white' => '/#ffffff(ff)?(?!\w)|#fff(f)?(?!\w)/i',
    ];

    public static function encode(string $svg): string
    {
        if (str_starts_with($svg, "\u{FEFF}")) {
            $svg = substr($svg, \strlen("\u{FEFF}"));
        }

        $body = JsValue::collapseWhitespace(JsValue::trim($svg));
        foreach (self::COLOR_NAMES as $name => $pattern) {
            $body = preg_replace($pattern, $name, $body);
        }
        $body = str_replace('"', "'", $body);

        $payload = strtr(rawurlencode($body), ['%21' => '!', '%2A' => '*', '%27' => "'", '%28' => '(', '%29' => ')']);

        $normalizeEscape = static fn (array $matches): string => match ($matches[0]) {
            '%20' => ' ',
            '%3D' => '=',
            '%3A' => ':',
            '%2F' => '/',
            default => strtolower($matches[0]),
        };

        return 'data:image/svg+xml,'.preg_replace_callback('/%[0-9A-F]{2}/', $normalizeEscape, $payload);
    }
}
