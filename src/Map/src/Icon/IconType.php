<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Icon;

/**
 * Represents an inline SVG icon.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
enum IconType: string
{
    case Url = 'url';
    case Svg = 'svg';
    case UxIcon = 'ux-icon';
}
