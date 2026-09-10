<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Disclose\Tests\Fixtures;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\UX\Disclose\DiscloseBundle;
use Symfony\UX\Disclose\Tests\Fixtures\Discloser\DummyDiscloser;
use Symfony\UX\Disclose\Tests\Fixtures\Resolver\InMemorySubjectResolver;
use Symfony\UX\Disclose\Tests\Fixtures\Subject\ServerSubjectFactory;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;

/**
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new SecurityBundle();
        yield new StimulusBundle();
        yield new TwigComponentBundle();
        yield new DiscloseBundle();
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'S3CRET',
            'http_method_override' => false,
            'test' => true,
            'router' => ['utf8' => true],
            'secrets' => false,
            'session' => ['storage_factory_id' => 'session.storage.factory.mock_file'],
            'translator' => ['fallbacks' => ['en']],
            'rate_limiter' => [
                'ux_disclose' => [
                    // cache.system is an in-process array pool when debug is on,
                    // so the budget is reset on every kernel boot (per test).
                    'policy' => 'fixed_window',
                    'limit' => 2,
                    'interval' => '1 hour',
                    'cache_pool' => 'cache.system',
                ],
            ],
        ]);

        $container->extension('twig', [
            'default_path' => '%kernel.project_dir%/tests/Fixtures/templates',
        ]);

        $container->extension('twig_component', [
            'defaults' => [
                'Symfony\UX\Disclose\Tests\Fixtures\TwigComponents\\' => ['template_directory' => 'components'],
            ],
            'anonymous_template_directory' => 'components',
        ]);

        $container->extension('security', [
            'password_hashers' => [
                PasswordAuthenticatedUserInterface::class => 'plaintext',
            ],
            'providers' => [
                'users_in_memory' => [
                    'memory' => [
                        'users' => [
                            'mr_discloser' => ['password' => 'symfonypass', 'roles' => ['ROLE_USER']],
                        ],
                    ],
                ],
            ],
            'firewalls' => [
                'main' => [
                    'http_basic' => true,
                ],
            ],
        ]);

        $container->extension('disclose', [
            'logger' => 'test.logger',
            'rate_limiter_subject_factory' => ServerSubjectFactory::class,
        ]);

        $services = $container->services();
        $services
            ->defaults()
                ->autowire()
                ->autoconfigure()
        ;

        $services->set(InMemorySubjectResolver::class);
        $services->set(DummyDiscloser::class);
        $services->set(ServerSubjectFactory::class);
        $services->set('test.logger', CollectingTestLogger::class)
            ->public()
        ;
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('@DiscloseBundle/config/routes.php')
            ->prefix('/test/disclose')
        ;
    }
}
