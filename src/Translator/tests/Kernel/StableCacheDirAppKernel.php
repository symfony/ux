<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Translator\Tests\Kernel;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class StableCacheDirAppKernel extends FrameworkAppKernel
{
    public function __construct(
        private string $tmpDir,
    ) {
        parent::__construct('test', true);
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);

        $loader->load(function (ContainerBuilder $container) {
            $container->loadFromExtension('ux_translator', [
                'dump_directory' => $this->getDumpDir(),
            ]);
        });
    }

    public function getCacheDir(): string
    {
        return $this->tmpDir.'/cache';
    }

    public function getLogDir(): string
    {
        return $this->tmpDir.'/logs';
    }

    public function getDumpDir(): string
    {
        return $this->tmpDir.'/translations';
    }
}
