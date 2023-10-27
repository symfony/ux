<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Router;

use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @final
 *
 * @experimental
 */
class RoutesDumper
{
    public function __construct(
        private string     $dumpDir,
        private Filesystem $filesystem,
    )
    {
    }

    public function dump(/* TODO */): void
    {
        $this->filesystem->mkdir($this->dumpDir);
        $this->filesystem->remove($this->dumpDir . '/index.js');
        $this->filesystem->remove($this->dumpDir . '/index.d.ts');
        $this->filesystem->remove($this->dumpDir . '/configuration.js');
        $this->filesystem->remove($this->dumpDir . '/configuration.d.ts');

        // TODO
    }
}
