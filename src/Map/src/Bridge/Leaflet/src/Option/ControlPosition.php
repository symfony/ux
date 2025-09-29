<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Bridge\Leaflet\Option;

/**
 * @see https://leafletjs.com/reference.html#control-position
 */
enum ControlPosition: string
{
    case TOP_LEFT = 'topleft';
    case TOP_RIGHT = 'topright';
    case BOTTOM_LEFT = 'bottomleft';
    case BOTTOM_RIGHT = 'bottomright';
}
