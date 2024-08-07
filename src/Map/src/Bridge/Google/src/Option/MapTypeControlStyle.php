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
 * Identifiers for common MapTypesControls.
 *
 * @see https://developers.google.com/maps/documentation/javascript/reference/control#MapTypeControlStyle
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
enum MapTypeControlStyle: int
{
    /**
     * Uses the default map type control. When the DEFAULT control is shown, it will vary according to window size and other factors.
     * The DEFAULT control may change in future versions of the API.
     */
    case DEFAULT = 0;

    /**
     * A dropdown menu for the screen realestate conscious.
     */
    case DROPDOWN_MENU = 2;

    /**
     * The standard horizontal radio buttons bar.
     */
    case HORIZONTAL_BAR = 1;
}
