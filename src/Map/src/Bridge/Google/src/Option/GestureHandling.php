<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Bridge\Google\Option;

/**
 * This setting controls how the API handles gestures on the map.
 *
 * @see https://developers.google.com/maps/documentation/javascript/reference/map#MapOptions.gestureHandling
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
enum GestureHandling: string
{
    /**
     * Scroll events and one-finger touch gestures scroll the page, and do not zoom or pan the map.
     * Two-finger touch gestures pan and zoom the map.
     * Scroll events with a ctrl key or ⌘ key pressed zoom the map.
     * In this mode the map cooperates with the page.
     */
    case COOPERATIVE = 'cooperative';

    /**
     * All touch gestures and scroll events pan or zoom the map.
     */
    case GREEDY = 'greedy';

    /**
     * The map cannot be panned or zoomed by user gestures.
     */
    case NONE = 'none';

    /**
     * Gesture handling is either cooperative or greedy, depending on whether the page is scrollable or in an iframe.
     */
    case AUTO = 'auto';
}
