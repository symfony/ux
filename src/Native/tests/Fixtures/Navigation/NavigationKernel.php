<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Native\Tests\Fixtures\Navigation;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\Native\UXNativeBundle;

/**
 * @internal
 */
final class NavigationKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [new FrameworkBundle(), new UXNativeBundle()];
    }

    public function getProjectDir(): string
    {
        return sys_get_temp_dir().'/ux_native_navigation';
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir().'/cache';
    }

    public function getLogDir(): string
    {
        return $this->getProjectDir().'/logs';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'test' => true,
            'secret' => 'test',
            'http_method_override' => false,
            'session' => ['storage_factory_id' => 'session.storage.factory.mock_file'],
            'router' => ['utf8' => true],
            // FrameworkBundle adds the same header to every response in debug mode
            'disallow_search_engine_index' => false,
        ]);

        $container->services()
            ->set(NoteController::class)
                ->autowire()
                ->tag('controller.service_arguments')
        ;
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(__DIR__.'/../../../config/routes.php');
        $routes->add('app_notes', '/notes')->controller([NoteController::class, 'index'])->methods(['GET']);
        $routes->add('app_note_new', '/notes/new')->controller([NoteController::class, 'new'])->methods(['POST']);
    }
}
