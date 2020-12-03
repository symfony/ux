<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Symfony\UX\LazyImage\Kernel;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @internal
 */
trait AppKernelTrait
{
    public function getCacheDir()
    {
        return $this->createTmpDir('cache');
    }

    public function getLogDir()
    {
        return $this->createTmpDir('logs');
    }

    private function createTmpDir(string $type): string
    {
        $dir = sys_get_temp_dir().'/lazyimage_bundle/'.uniqid($type.'_', true);

        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        return $dir;
    }
}
