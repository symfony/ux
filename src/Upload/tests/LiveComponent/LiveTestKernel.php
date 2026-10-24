<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\LiveComponent;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\LiveComponent\LiveComponentBundle;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;
use Symfony\UX\Upload\UXUploadBundle;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

final class LiveTestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new TwigComponentBundle();
        yield new LiveComponentBundle();
        yield new StimulusBundle();
        yield new UXUploadBundle();
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'S3CRET',
            'test' => true,
            'http_method_override' => false,
            'csrf_protection' => ['enabled' => false],
            'router' => ['utf8' => true],
            'session' => ['storage_factory_id' => 'session.storage.factory.mock_file'],
        ]);

        $container->extension('twig', [
            'default_path' => '%kernel.project_dir%/tests/Fixtures/templates',
        ]);

        $container->extension('twig_component', [
            'anonymous_template_directory' => 'components/',
            'defaults' => [
                'Symfony\\UX\\Upload\\Tests\\Fixtures\\Component\\' => 'components/',
            ],
        ]);

        $container->extension('ux_upload', [
            'storage' => 'local',
            'temp_dir' => '%kernel.project_dir%/var/tests/uploads/tmp',
            'chunk_size' => '5M',
            'local_storage' => [
                'directory' => '%kernel.project_dir%/var/tests/uploads/storage',
            ],
        ]);

        $services = $container->services()
            ->defaults()
            ->autowire()
            ->autoconfigure();

        $services->load(
            'Symfony\\UX\\Upload\\Tests\\Fixtures\\Component\\',
            param('kernel.project_dir').'/tests/Fixtures/Component/',
        );
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('@LiveComponentBundle/config/routes.php')->prefix('/_components');
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir().'/var/cache/test_live';
    }

    public function getLogDir(): string
    {
        return $this->getProjectDir().'/var/log/test_live';
    }
}
