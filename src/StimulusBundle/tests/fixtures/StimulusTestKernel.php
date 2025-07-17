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
use Symfony\Component\AssetMapper\ImportMap\ImportMapConfigReader;
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
                'importmap_path' => '%kernel.project_dir%/'.(class_exists(ImportMapConfigReader::class) ? 'importmap.php' : 'legacy/importmap.php'),
            ],
            'test' => true,
            'handle_all_throwables' => true,
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
        ]);
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->register('logger', NullLogger::class);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('homepage', '/')->controller('kernel::homepage');
    }
}
