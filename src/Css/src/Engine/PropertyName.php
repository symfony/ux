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
 * Port of Panda's `hypenateProperty()` (packages/shared/src/hypenate-property.ts).
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class PropertyName
{
    public static function toCss(string $property): string
    {
        if (str_starts_with($property, '--')) {
            return $property;
        }

        return strtolower(preg_replace('/^ms-/', '-ms-', preg_replace('/([A-Z])/', '-$1', $property)));
    }
}
