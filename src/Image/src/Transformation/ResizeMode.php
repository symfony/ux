<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Transformation;

enum ResizeMode: string
{
    case Fit = 'fit';
    case Crop = 'crop';
    case Fill = 'fill';
}
