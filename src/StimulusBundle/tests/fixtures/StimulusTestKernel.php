<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\Tests\fixtures;

use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\AssetMapper\ImportMap\ImportMapGenerator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Twig\Environment;

class StimulusTestKernel extends Kernel
{
    use MicroKernelTrait;

    public function homepage(Environment $twig): Response
    {
        return new Response($twig->render('homepage.html.twig'));
    }

    public function front(Environment $twig): Response
    {
        return new Response($twig->render('front.html.twig'));
    }

    public function admin(Environment $twig): Response
    {
        return new Response($twig->render('admin.html.twig'));
    }

    public function shop(Environment $twig): Response
    {
        return new Response($twig->render('shop.html.twig'));
    }

    public function page(Environment $twig, string $template): Response
    {
        return new Response($twig->render($template.'.html.twig'));
    }

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new TwigBundle(),
            new StimulusBundle(),
        ];
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $isApplicationsEnvironment = str_starts_with($this->environment, 'applications');

        $container->extension('framework', [
            'secret' => 'foo000',
            'http_method_override' => false,
            'asset_mapper' => [
                'paths' => [
                    'assets' => '',
                    'in-asset-mapper' => 'in/asset/mapper',
                    __DIR__.'/vendor/fake-vendor/ux-package1/assets/dist' => 'fake-vendor/ux-package1',
                    __DIR__.'/vendor/fake-vendor/ux-package2/Resources/assets/dist' => 'fake-vendor/ux-package2',
                ],
                // @legacy
                'importmap_path' => $isApplicationsEnvironment ? '%kernel.project_dir%/importmap_applications.php' : '%kernel.project_dir%/importmap.php',
            ],
            'test' => true,
            ...(self::VERSION_ID >= 60200 ? [
                'handle_all_throwables' => true,
            ] : []),
            'php_errors' => ['log' => true],
        ]);

        $container->extension('twig', [
            'default_path' => '%kernel.project_dir%/templates',
        ]);

        $container->extension('stimulus', [
            'controller_paths' => [
                __DIR__.'/assets/controllers',
                __DIR__.'/assets/more-controllers',
            ],
            ...($isApplicationsEnvironment ? [
                'applications' => [
                    'front' => [
                        'loader' => '%kernel.project_dir%/assets/front/stimulus_loader.js',
                        'controller_paths' => [__DIR__.'/assets/front/controllers'],
                        'include_global' => true,
                    ],
                    'admin' => [
                        'controller_paths' => [__DIR__.'/assets/admin/controllers'],
                        'include_global' => false,
                        'controllers_json' => '%kernel.project_dir%/assets/admin/controllers.json',
                    ],
                    'shop' => null,
                    'account' => null,
                    'backoffice' => [
                        'loader' => '%kernel.project_dir%/in-asset-mapper/zones/backoffice/bootstrap.js',
                        'controller_paths' => [
                            __DIR__.'/in-asset-mapper/shared/controllers',
                            __DIR__.'/assets/admin/controllers',
                        ],
                        'include_global' => false,
                    ],
                    'partner' => [
                        'loader' => '%kernel.project_dir%/assets/partner_stimulus.js',
                        'controller_paths' => [
                            __DIR__.'/in-asset-mapper/shared/controllers',
                            __DIR__.'/assets/shop/controllers',
                        ],
                        'controllers_json' => '%kernel.project_dir%/assets/partner/controllers.json',
                        'merge_controllers_json' => true,
                    ],
                ],
            ] : []),
        ]);
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->register('logger', NullLogger::class);

        if ('applications_reachable' === $this->environment) {
            $container->addCompilerPass(new class implements CompilerPassInterface {
                public function process(ContainerBuilder $container): void
                {
                    $container->getDefinition('asset_mapper.importmap.generator')->setArgument(4, ImportMapGenerator::ENTRIES_REACHABLE);
                }
            });
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('homepage', '/')->controller('kernel::homepage');
        $routes->add('front', '/front')->controller('kernel::front');
        $routes->add('admin', '/admin')->controller('kernel::admin');
        $routes->add('shop', '/shop')->controller('kernel::shop');
        $routes->add('backoffice', '/backoffice')->controller('kernel::page')->defaults(['template' => 'backoffice']);
        $routes->add('partner', '/partner')->controller('kernel::page')->defaults(['template' => 'partner']);
        $routes->add('admin_with_app', '/admin-with-app')->controller('kernel::page')->defaults(['template' => 'admin_with_app']);
    }
}
