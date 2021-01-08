<?php

/*
 * This file is part of the Symfony UX Turbo package.
 *
 * (c) Kévin Dunglas <kevin@dunglas.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\TemplateController;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MercureBundle\MercureBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\Turbo\Stream\TurboStreamResponse;
use Symfony\UX\Turbo\TurboBundle;
use Symfony\WebpackEncoreBundle\WebpackEncoreBundle;
use Twig\Environment;

/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new DoctrineBundle();
        yield new TwigBundle();
        yield new MercureBundle();
        yield new TurboBundle();
        yield new WebpackEncoreBundle();
        yield new WebProfilerBundle();
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'test' => 'test' === $_SERVER['APP_ENV'] ?? 'dev',
            'router' => [
                'utf8' => true,
            ],
            'profiler' => [
                'only_exceptions' => false,
            ],
        ]);
        $container
            ->extension('doctrine', [
                'dbal' => [
                    'url' => 'sqlite:///%kernel.project_dir%/var/turbo.db',
                ],
                'orm' => [
                    'auto_generate_proxy_classes' => true,
                    'auto_mapping' => true,
                    'mappings' => [
                        'App' => [
                            'is_bundle' => false,
                            'type' => 'annotation',
                            'dir' => '%kernel.project_dir%/Entity',
                            'prefix' => 'App\Entity',
                            'alias' => 'App',
                        ],
                    ],
                ],
            ]);
        $container->extension('webpack_encore', ['output_path' => 'build']);
        $container->extension('web_profiler', [
            'toolbar' => true,
            'intercept_redirects' => false,
        ]);

        $container->extension('mercure', [
            'enable_profiler' => '%kernel.debug%',
            'hubs' => [
                'default' => [
                    'url' => $_SERVER['MERCURE_PUBLISH_URL'] ?? 'http://localhost:3000/.well-known/mercure',
                    'jwt' => $_SERVER['MERCURE_JWT_TOKEN'] ?? 'eyJhbGciOiJIUzI1NiJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOlsiKiJdfX0.vhMwOaN5K68BTIhWokMLOeOJO4EPfT64brd8euJOA4M',
                ],
            ],
        ]);

        $container->extension('turbo', [
            'mercure' => [
                'subscribe_url' => $_SERVER['MERCURE_SUBSCRIBE_URL'] ?? 'http://localhost:3000/.well-known/mercure',
            ],
        ]);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('@WebProfilerBundle/Resources/config/routing/wdt.xml')->prefix('/_wdt');
        $routes->import('@WebProfilerBundle/Resources/config/routing/profiler.xml')->prefix('/_profiler');

        $routes->add('home', '/')->controller(TemplateController::class)->defaults(['template' => 'home.html.twig']);
        $routes->add('block', '/block')->controller(TemplateController::class)->defaults(['template' => 'block.html.twig']);
        $routes->add('lazy', '/lazy')->controller(TemplateController::class)->defaults(['template' => 'lazy.html.twig']);
        $routes->add('form', '/form')->controller('kernel:form');
        $routes->add('chat', '/chat')->controller('kernel:chat');
        $routes->add('books', '/books')->controller('kernel:books');
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function form(Request $request, Environment $twig): Response
    {
        if (TurboStreamResponse::STREAM_FORMAT === $request->getPreferredFormat()) {
            return new TurboStreamResponse($twig->render('form.stream.html.twig'));
        }

        return new Response('Turbo not installed, default to plain HTML.');
    }

    public function chat(Request $request, FormFactoryInterface $formFactory, PublisherInterface $mercure, Environment $twig): Response
    {
        $form = $formFactory
            ->createBuilder(FormType::class)
            ->add('message', TextType::class, ['attr' => ['autocomplete' => 'off']])
            ->add('send', SubmitType::class)
            ->getForm();

        $newForm = clone $form; // To display an empty form
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $mercure(new Update('chat', $twig->render('chat/message.stream.html.twig', ['message' => $data['message']])));
        }

        return new Response(
            $twig->render('chat/index.html.twig', ['form' => $newForm->createView()])
        );
    }

    public function books(Request $request, EntityManagerInterface $doctrine, Environment $twig): Response
    {
        if ($request->isMethod('POST')) {
            if ($id = $request->get('id')) {
                if (!($book = $doctrine->find(Book::class, $id))) {
                    throw new NotFoundHttpException();
                }
            } else {
                $book = new Book();
            }
            if ($title = $request->get('title')) {
                $book->title = $title;
            }
            if ($remove = $request->get('remove')) {
                $doctrine->remove($book);
            } else {
                $doctrine->persist($book);
            }

            $doctrine->flush();
        }

        return new Response($twig->render('books.html.twig'));
    }
}
