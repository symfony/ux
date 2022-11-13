<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Fixtures;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\LiveComponent\LiveComponentBundle;
use Symfony\UX\LiveComponent\Tests\Fixtures\Serializer\Entity2Normalizer;
use Symfony\UX\LiveComponent\Tests\Fixtures\Serializer\MoneyNormalizer;
use Symfony\UX\TwigComponent\TwigComponentBundle;
use Twig\Environment;
use Zenstruck\Foundry\ZenstruckFoundryBundle;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function index(): Response
    {
        return new Response('index');
    }

    public function renderTemplate(string $template, Environment $twig = null): Response
    {
        $twig ??= $this->container->get('twig');

        return new Response($twig->render("{$template}.html.twig"));
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new DoctrineBundle();
        yield new ZenstruckFoundryBundle();
        yield new TwigComponentBundle();
        yield new LiveComponentBundle();
        yield new ZenstruckFoundryBundle();
    }

    protected function configureContainer(ContainerConfigurator $c): void
    {
        $c->extension('framework', [
            'secret' => 'S3CRET',
            'test' => true,
            'router' => ['utf8' => true],
            'secrets' => false,
            'session' => ['storage_factory_id' => 'session.storage.factory.mock_file'],
            'http_method_override' => false,
        ]);

        $c->extension('twig', [
            'default_path' => '%kernel.project_dir%/tests/Fixtures/templates',
        ]);

        $c->extension('zenstruck_foundry', [
            'auto_refresh_proxies' => false,
        ]);

        $c->extension('doctrine', [
            'dbal' => ['url' => '%env(resolve:DATABASE_URL)%'],
            'orm' => [
                'auto_generate_proxy_classes' => true,
                'auto_mapping' => true,
                'mappings' => [
                    'Default' => [
                        'is_bundle' => false,
                        'type' => 'annotation',
                        'dir' => '%kernel.project_dir%/tests/Fixtures/Entity',
                        'prefix' => 'Symfony\UX\LiveComponent\Tests\Fixtures\Entity',
                        'alias' => 'Default',
                    ],
                    'XML' => [
                        'is_bundle' => false,
                        'type' => 'xml',
                        'dir' => '%kernel.project_dir%/tests/Fixtures/config/doctrine',
                        'prefix' => 'Symfony\UX\LiveComponent\Tests\Fixtures\Dto',
                        'alias' => 'XML',
                    ],
                ],
            ],
        ]);

        $c->extension('zenstruck_foundry', [
            'auto_refresh_proxies' => false,
        ]);

        $c->services()
            ->defaults()
                ->autowire()
                ->autoconfigure()
            // disable logging errors to the console
            ->set('logger', NullLogger::class)
            ->set(MoneyNormalizer::class)->autoconfigure()->autowire()
            ->set(Entity2Normalizer::class)->autoconfigure()->autowire()
            ->load(__NAMESPACE__.'\\Component\\', __DIR__.'/Component')
        ;
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('@LiveComponentBundle/config/routes.php')
            ->prefix('/_components');

        $routes->add('template', '/render-template/{template}')->controller('kernel::renderTemplate');
        $routes->add('homepage', '/')->controller('kernel::index');
    }
}
