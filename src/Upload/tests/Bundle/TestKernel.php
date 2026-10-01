<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Bundle;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\Upload\UXUploadBundle;

final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new UXUploadBundle();
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'S0CRET',
            'http_method_override' => false,
            'test' => true,
        ]);

        $container->extension('ux_upload', [
            'storage' => 'local',
            'temp_dir' => '%kernel.project_dir%/var/tests/uploads/tmp',
            'chunk_size' => '5M',
            'local_storage' => [
                'directory' => '%kernel.project_dir%/var/tests/uploads/storage',
            ],
        ]);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir().'/var/cache/test_bundle';
    }

    public function getLogDir(): string
    {
        return $this->getProjectDir().'/var/log/test_bundle';
    }
}
