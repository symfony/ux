<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Tests\Fixtures;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\DataTable\UXDataTableBundle;
use Symfony\UX\LiveComponent\LiveComponentBundle;
use Symfony\UX\Pagination\UXPaginationBundle;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;

final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new StimulusBundle();
        yield new TwigComponentBundle();
        yield new LiveComponentBundle();
        yield new UXPaginationBundle();
        yield new UXDataTableBundle();
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/ux_data_table/'.$this->environment.'/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/ux_data_table/'.$this->environment.'/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'secret',
            'test' => true,
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'php_errors' => ['log' => true],
            'form' => true,
            'csrf_protection' => true,
            'session' => ['storage_factory_id' => 'session.storage.factory.mock_file'],
            'translator' => ['fallbacks' => ['en']],
        ]);
        $container->extension('twig', ['default_path' => __DIR__.'/templates']);
        $container->extension('twig_component', ['defaults' => [], 'anonymous_template_directory' => 'components/']);

        if ('bootstrap' === $this->environment) {
            $container->extension('ux_data_table', ['theme' => '@UXDataTable/theme/bootstrap_5.html.twig']);
        }
        if ('tabler' === $this->environment) {
            $container->extension('ux_data_table', ['theme' => '@UXDataTable/theme/tabler.html.twig']);
        }
        if ('custom' === $this->environment) {
            $container->extension('ux_data_table', ['theme' => 'custom_theme.html.twig']);
        }

        $container->services()
            ->defaults()->autowire()->autoconfigure()
            ->set(ProductController::class)->public()
            ->set(ProductsTableComponent::class)
            ->set(CurrencyColumnType::class)
        ;
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(ProductController::class, 'attribute');
        $routes->import('@LiveComponentBundle/config/routes.php');
    }
}
