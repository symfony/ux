<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Rendering;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\Upload\UXUploadBundle;

/**
 * Boots just enough of a framework to render the package form theme.
 *
 * The theme needs Twig and StimulusBundle, and the widget builds its endpoint
 * URLs from the package routes, so those are imported too. Nothing here serves
 * HTTP: the rendering tests assert on the produced markup, not on a browser.
 */
final class RenderingKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new StimulusBundle();
        yield new UXUploadBundle();
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'ux-upload-rendering',
            'http_method_override' => false,
            'test' => true,
            'router' => ['utf8' => true],
        ]);

        $container->extension('twig', [
            'default_path' => __DIR__.'/templates',
            'form_themes' => ['upload_global_theme.html.twig'],
        ]);

        $container->extension('ux_upload', [
            'storage' => 'local',
            'temp_dir' => '%kernel.project_dir%/var/tests/rendering/tmp',
            'local_storage' => [
                'directory' => '%kernel.project_dir%/var/tests/rendering/storage',
            ],
        ]);

        // Nothing consumes these here, so the container would inline them away.
        $container->services()
            ->alias('test.form.factory', 'form.factory')->public()
            ->alias('test.twig', 'twig')->public()
        ;
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(\dirname(__DIR__, 2).'/config/routes.php')
            ->prefix('/_upload', false);
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir().'/var/cache/test_rendering';
    }

    public function getLogDir(): string
    {
        return $this->getProjectDir().'/var/log/test_rendering';
    }
}
