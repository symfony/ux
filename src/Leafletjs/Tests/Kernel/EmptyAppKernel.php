<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Leafletjs\Tests\Kernel;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\UX\Leafletjs\LeafletjsBundle;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 * @author Michael Cramer <michael@bigmichi1.de>
 *
 * @internal
 */
class EmptyAppKernel extends Kernel
{
    use AppKernelTrait;

    public function registerBundles()
    {
        return [new LeafletjsBundle()];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
    }
}
