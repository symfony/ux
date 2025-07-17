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
 * @see https://developers.google.com/maps/documentation/javascript/reference/control#ControlPosition
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
enum ControlPosition: int
{
    /**
     * Equivalent to bottom-center in both LTR and RTL.
     */
    case BLOCK_END_INLINE_CENTER = 24;

    /**
     * Equivalent to bottom-right in LTR, or bottom-left in RTL.
     */
    case BLOCK_END_INLINE_END = 25;

    /**
     * Equivalent to bottom-left in LTR, or bottom-right in RTL.
     */
    case BLOCK_END_INLINE_START = 23;

    /**
     * Equivalent to top-center in both LTR and RTL.
     */
    case BLOCK_START_INLINE_CENTER = 15;

    /**
     * Equivalent to top-right in LTR, or top-left in RTL.
     */
    case BLOCK_START_INLINE_END = 16;

    /**
     * Equivalent to top-left in LTR, or top-right in RTL.
     */
    case BLOCK_START_INLINE_START = 14;

    /**
     * Equivalent to right-center in LTR, or left-center in RTL.
     */
    case INLINE_END_BLOCK_CENTER = 21;

    /**
     * Equivalent to right-bottom in LTR, or left-bottom in RTL.
     */
    case INLINE_END_BLOCK_END = 22;

    /**
     * Equivalent to right-top in LTR, or left-top in RTL.
     */
    case INLINE_END_BLOCK_START = 20;

    /**
     * Equivalent to left-center in LTR, or right-center in RTL.
     */
    case INLINE_START_BLOCK_CENTER = 17;

    /**
     * Equivalent to left-bottom in LTR, or right-bottom in RTL.
     */
    case INLINE_START_BLOCK_END = 19;

    /**
     * Equivalent to left-top in LTR, or right-top in RTL.
     */
    case INLINE_START_BLOCK_START = 18;
}
