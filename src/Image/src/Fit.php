<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image;

/**
 * The resize behaviours every supported provider implements.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
enum Fit: string
{
    case Cover = 'cover';
    case Contain = 'contain';
    case ScaleDown = 'scale-down';
}
