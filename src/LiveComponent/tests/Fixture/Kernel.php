<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Fixture;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Symfony\UX\LiveComponent\LiveComponentBundle;
use Symfony\UX\LiveComponent\Tests\Fixture\Component\Component1;
use Symfony\UX\LiveComponent\Tests\Fixture\Component\Component2;
use Symfony\UX\LiveComponent\Tests\Fixture\Component\Component3;
use Symfony\UX\TwigComponent\TwigComponentBundle;
use Twig\Environment;

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
        yield new TwigComponentBundle();
        yield new LiveComponentBundle();
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        // disable logging errors to the console
        $c->register('logger', NullLogger::class);

        $c->register(Component1::class)->setAutoconfigured(true)->setAutowired(true);
        $c->register(Component2::class)->setAutoconfigured(true)->setAutowired(true);
        $c->register(Component3::class)->setAutoconfigured(true)->setAutowired(true);

        $sessionConfig = self::VERSION_ID < 50300 ? ['storage_id' => 'session.storage.mock_file'] : ['storage_factory_id' => 'session.storage.factory.mock_file'];

        $c->loadFromExtension('framework', [
            'secret' => 'S3CRET',
            'test' => true,
            'router' => ['utf8' => true],
            'secrets' => false,
            'session' => $sessionConfig,
        ]);

        $c->loadFromExtension('twig', [
            'default_path' => '%kernel.project_dir%/tests/Fixture/templates',
        ]);

        $c->loadFromExtension('doctrine', [
            'dbal' => ['url' => '%env(resolve:DATABASE_URL)%'],
            'orm' => [
                'auto_generate_proxy_classes' => true,
                'auto_mapping' => true,
                'mappings' => [
                    'Test' => [
                        'is_bundle' => false,
                        'type' => 'annotation',
                        'dir' => '%kernel.project_dir%/tests/Fixture/Entity',
                        'prefix' => 'Symfony\UX\LiveComponent\Tests\Fixture\Entity',
                        'alias' => 'Test',
                    ],
                ],
            ],
        ]);
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $routes->import('@LiveComponentBundle/Resources/config/routing/live_component.xml');

        $routes->add('/render-template/{template}', 'kernel::renderTemplate');
        $routes->add('/', 'kernel::index');
    }
}
