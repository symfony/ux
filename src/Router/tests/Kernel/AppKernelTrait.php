<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Router\Tests\Kernel;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
trait AppKernelTrait
{
    public function getCacheDir(): string
    {
        return $this->createTmpDir('cache');
    }

    public function getLogDir(): string
    {
        return $this->createTmpDir('logs');
    }

    private function createTmpDir(string $type): string
    {
        $dir = sys_get_temp_dir().'/router_bundle/'.uniqid($type.'_', true);

        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        return $dir;
    }
}
