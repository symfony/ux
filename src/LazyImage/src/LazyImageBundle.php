<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LazyImage;

use Symfony\Component\HttpKernel\Bundle\Bundle;

trigger_deprecation('symfony/ux-lazy-image', '2.27.0', 'The package is deprecated and will be removed in 3.0.');

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @final
 */
class LazyImageBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
