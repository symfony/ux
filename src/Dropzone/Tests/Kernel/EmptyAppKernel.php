<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Symfony\UX\Dropzone\Kernel;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\UX\Dropzone\DropzoneBundle;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @internal
 */
class EmptyAppKernel extends Kernel
{
    use AppKernelTrait;

    public function registerBundles()
    {
        return [new DropzoneBundle()];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
    }
}
