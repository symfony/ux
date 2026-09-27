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
 * Port of Panda's unitless properties (packages/core/src/unitless.ts): numbers stay without `px`.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class Unitless
{
    private const PROPERTIES = [
        'animationIterationCount' => true,
        'aspectRatio' => true,
        'borderImageOutset' => true,
        'borderImageSlice' => true,
        'borderImageWidth' => true,
        'boxFlex' => true,
        'boxFlexGroup' => true,
        'boxOrdinalGroup' => true,
        'columnCount' => true,
        'columns' => true,
        'flex' => true,
        'flexGrow' => true,
        'flexPositive' => true,
        'flexShrink' => true,
        'flexNegative' => true,
        'flexOrder' => true,
        'gridRow' => true,
        'gridRowEnd' => true,
        'gridRowSpan' => true,
        'gridRowStart' => true,
        'gridColumn' => true,
        'gridColumnEnd' => true,
        'gridColumnSpan' => true,
        'gridColumnStart' => true,
        'msGridRow' => true,
        'msGridRowSpan' => true,
        'msGridColumn' => true,
        'msGridColumnSpan' => true,
        'fontWeight' => true,
        'lineClamp' => true,
        'lineHeight' => true,
        'opacity' => true,
        'order' => true,
        'orphans' => true,
        'scale' => true,
        'tabSize' => true,
        'widows' => true,
        'zIndex' => true,
        'zoom' => true,
        'WebkitLineClamp' => true,
        'fillOpacity' => true,
        'floodOpacity' => true,
        'stopOpacity' => true,
        'strokeDasharray' => true,
        'strokeDashoffset' => true,
        'strokeMiterlimit' => true,
        'strokeOpacity' => true,
        'strokeWidth' => true,
    ];

    public static function has(string $property): bool
    {
        return isset(self::PROPERTIES[$property]) || isset(self::hyphenated()[$property]);
    }

    /**
     * @return array<string, true>
     */
    private static function hyphenated(): array
    {
        static $hyphenated;

        return $hyphenated ??= array_fill_keys(array_map(PropertyName::toCss(...), array_keys(self::PROPERTIES)), true);
    }
}
