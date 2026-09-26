<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb\Tests\Fixtures;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\Breadcrumb\Tests\Fixtures\Controller\DashboardHomeController;
use Symfony\UX\Breadcrumb\Tests\Fixtures\Controller\PlainController;
use Symfony\UX\Breadcrumb\Tests\Fixtures\Controller\ProductIndexController;
use Symfony\UX\Breadcrumb\Tests\Fixtures\Controller\ProductRedirectController;
use Symfony\UX\Breadcrumb\Tests\Fixtures\Controller\ProductViewController;
use Symfony\UX\Breadcrumb\UXBreadcrumbBundle;

final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new UXBreadcrumbBundle();
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // The bundle registers everything private; the tests assert on the real services.
        $container->addCompilerPass(new class implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                foreach (['ux_breadcrumb.resolver', 'ux_breadcrumb.trail_provider', 'ux_breadcrumb.expression_language', 'ux_breadcrumb.twig.extension', 'request_stack', 'translator'] as $id) {
                    if ($container->hasDefinition($id)) {
                        $container->getDefinition($id)->setPublic(true);
                    }
                }
            }
        }, PassConfig::TYPE_BEFORE_REMOVING);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('dashboard_home', '/dashboard')->controller(DashboardHomeController::class);
        $routes->add('product_index', '/products')->controller(ProductIndexController::class);
        $routes->add('product_view', '/products/{slug}')->controller(ProductViewController::class);
        $routes->add('product_redirect', '/products/{slug}/redirect')->controller(ProductRedirectController::class);
        $routes->add('plain', '/plain')->controller(PlainController::class);
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container): void {
            // MicroKernelTrait normally wires this up; we override its hook entirely.
            if (!$container->hasDefinition('kernel')) {
                $container->register('kernel', static::class)
                    ->addTag('controller.service_arguments')
                    ->setAutoconfigured(true)
                    ->setSynthetic(true)
                    ->setPublic(true);
            }
            $container->getDefinition('kernel')->addTag('routing.route_loader');

            $container->loadFromExtension('framework', [
                'secret' => 'test-secret',
                'test' => true,
                'http_method_override' => false,
                'handle_all_throwables' => true,
                'php_errors' => ['log' => true],
                'router' => ['resource' => 'kernel::loadRoutes', 'type' => 'service', 'utf8' => true],
                'translator' => ['fallbacks' => ['en'], 'paths' => [__DIR__.'/translations']],
            ]);
            $container->loadFromExtension('twig', ['strict_variables' => true]);
            $container->loadFromExtension('ux_breadcrumb', []);

            foreach ([
                DashboardHomeController::class,
                PlainController::class,
                ProductIndexController::class,
                ProductRedirectController::class,
                ProductViewController::class,
            ] as $controller) {
                $container->register($controller, $controller)
                    ->setAutowired(true)
                    ->setAutoconfigured(true)
                    ->setPublic(true)
                    ->addTag('controller.service_arguments');
            }

            $container->register(ProductValueResolver::class, ProductValueResolver::class)
                ->setAutoconfigured(true)
                ->addTag('controller.argument_value_resolver', ['priority' => 200]);

            $container->register(FilterStateExpressionFunctionProvider::class, FilterStateExpressionFunctionProvider::class)
                ->addTag('ux_breadcrumb.expression_function_provider');

            if ('no_root_provider' !== $this->environment) {
                $container->register(RootCrumbProvider::class, RootCrumbProvider::class)
                    ->setAutoconfigured(true);
            }

            if ('counting_translator' === $this->environment) {
                $container->register('translator', CountingTranslator::class)->setPublic(true);
            }
        });
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/ux-breadcrumb/cache/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/ux-breadcrumb/log';
    }
}
