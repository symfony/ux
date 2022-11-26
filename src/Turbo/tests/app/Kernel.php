<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App;

use App\Entity\Artist;
use App\Entity\Book;
use App\Entity\Song;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\DebugBundle\DebugBundle;
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
use Symfony\Component\Mercure\Hub;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
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
        yield new DebugBundle();
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'ChangeMe',
            'test' => 'test' === ($_SERVER['APP_ENV'] ?? 'dev'),
            'router' => [
                'utf8' => true,
            ],
            'profiler' => [
                'only_exceptions' => false,
            ],
        ]);

        $doctrineConfig = [
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
        ];

        $container
            ->extension('doctrine', $doctrineConfig);

        $container->extension('webpack_encore', ['output_path' => 'build']);
        $container->extension('web_profiler', [
            'toolbar' => true,
            'intercept_redirects' => false,
        ]);

        $container->extension('mercure', [
            'hubs' => [
                'default' => [
                    'url' => $_SERVER['MERCURE_PUBLISH_URL'] ?? 'http://127.0.0.1:3000/.well-known/mercure',
                    'jwt' => $_SERVER['MERCURE_JWT_TOKEN'] ?? 'eyJhbGciOiJIUzI1NiJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOlsiKiJdfX0.vhMwOaN5K68BTIhWokMLOeOJO4EPfT64brd8euJOA4M',
                ],
            ],
        ]);

        $container->services()->alias(Hub::class, 'mercure.hub.default'); // FIXME: temporary fix for a bug in https://github.com/symfony/mercure-bundle/pull/42
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('@WebProfilerBundle/Resources/config/routing/wdt.xml')->prefix('/_wdt');
        $routes->import('@WebProfilerBundle/Resources/config/routing/profiler.xml')->prefix('/_profiler');

        $routes->add('home', '/')->controller(TemplateController::class)->defaults(['template' => 'home.html.twig']);
        $routes->add('block', '/block')->controller(TemplateController::class)->defaults(['template' => 'block.html.twig']);
        $routes->add('lazy', '/lazy')->controller(TemplateController::class)->defaults(['template' => 'lazy.html.twig']);
        $routes->add('form', '/form')->controller('kernel::form');
        $routes->add('chat', '/chat')->controller('kernel::chat');
        $routes->add('books', '/books')->controller('kernel::books');
        $routes->add('songs', '/songs')->controller('kernel::songs');
        $routes->add('artists', '/artists')->controller('kernel::artists');
        $routes->add('artist', '/artists/{id}')->controller('kernel::artist');
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function form(Request $request, Environment $twig): Response
    {
        if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            $response = (new Response($twig->render('form.stream.html.twig')));
        } else {
            $response = new Response('Turbo not installed, default to plain HTML.');
        }

        return $response->setVary('Accept');
    }

    public function chat(Request $request, FormFactoryInterface $formFactory, HubInterface $mercureHub, Environment $twig): Response
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

            $mercureHub->publish(new Update('chat', $twig->render('chat/message.stream.html.twig', ['message' => $data['message']])));
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

    public function songs(Request $request, EntityManagerInterface $doctrine, Environment $twig): Response
    {
        if ($request->isMethod('POST')) {
            if ($id = $request->get('id')) {
                if (!($song = $doctrine->find(Song::class, $id))) {
                    throw new NotFoundHttpException();
                }
            } else {
                $song = new Song();
            }
            if ($title = $request->get('title')) {
                $song->title = $title;
                $artistId = $request->get('artistId');
                if ($artistId > 0) {
                    $song->artist = $doctrine->find(Artist::class, $artistId);
                }
            }
            if ($remove = $request->get('remove')) {
                $doctrine->remove($song);
            } else {
                $doctrine->persist($song);
            }

            $doctrine->flush();
        }

        return new Response($twig->render('songs.html.twig'));
    }

    public function artists(Request $request, EntityManagerInterface $doctrine, Environment $twig): Response
    {
        if ($request->isMethod('POST')) {
            if ($id = $request->get('id')) {
                if (!($artist = $doctrine->find(Artist::class, $id))) {
                    throw new NotFoundHttpException();
                }
            } else {
                $artist = new Artist();
            }
            if ($name = $request->get('name')) {
                $artist->name = $name;
            }
            if ($remove = $request->get('remove')) {
                $doctrine->remove($artist);
            } else {
                $doctrine->persist($artist);
            }

            $doctrine->flush();
        }

        return new Response($twig->render('artists.html.twig'));
    }

    public function artist(Request $request, EntityManagerInterface $doctrine, Environment $twig): Response
    {
        $id = $request->get('id');

        if (!($artist = $doctrine->find(Artist::class, $id))) {
            throw new NotFoundHttpException();
        }

        return new Response($twig->render('artist.html.twig', ['artist' => $artist]));
    }
}
