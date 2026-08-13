<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\E2E;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\Upload\UXUploadBundle;

/**
 * Minimal HTTP kernel booted over the wire by {@see UploadE2ETest}.
 *
 * Unlike the in-process functional kernels, this one must be reachable via real
 * HTTP so Playwright can drive a browser against it. It renders the FileUploadType
 * through the package's own form theme (which requires StimulusBundle for the
 * stimulus_* Twig functions) and exposes a single /upload/test page plus the
 * bundle's upload endpoints. CSRF is intentionally left disabled so the upload
 * flow exercises the real controller without token juggling.
 *
 * The cache, log and upload-storage directories all live under the directory
 * given by the UX_UPLOAD_E2E_DIR environment variable, so the test owns and
 * cleans that state.
 */
final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    private function workingDir(): string
    {
        return getenv('UX_UPLOAD_E2E_DIR') ?: sys_get_temp_dir().'/ux_upload_e2e';
    }

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
            'secret' => 'ux-upload-e2e',
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'php_errors' => ['log' => true],
            'router' => ['utf8' => true],
        ]);

        $container->extension('twig', [
            'default_path' => __DIR__.'/templates',
            'form_themes' => ['upload_global_theme.html.twig'],
        ]);

        $container->extension('ux_upload', [
            'storage' => 'local',
            'temp_dir' => $this->workingDir().'/uploads/tmp',
            'local_storage' => [
                'directory' => $this->workingDir().'/uploads/storage',
            ],
        ]);

        $container->services()
            ->set(UploadTestController::class)
            ->autowire()
            ->autoconfigure()
            ->public();
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(\dirname(__DIR__, 2).'/config/routes.php')
            ->prefix('/_upload', false);
        $routes->add('e2e_upload_test', '/upload/test')
            ->controller(UploadTestController::class);
    }

    public function getCacheDir(): string
    {
        return $this->workingDir().'/cache';
    }

    public function getLogDir(): string
    {
        return $this->workingDir().'/log';
    }
}
