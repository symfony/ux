<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Fixtures;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\Upload\Security\UploadContextResolverInterface;
use Symfony\UX\Upload\Tests\Fixtures\Form\ProfileType;
use Symfony\UX\Upload\Tests\Fixtures\Security\RequestHeaderUploadContextResolver;
use Symfony\UX\Upload\Upload\UploadedFile;
use Symfony\UX\Upload\UXUploadBundle;
use Twig\Environment;

/**
 * The "anonymous_disabled" environment keeps the default "allow_anonymous: false".
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function profile(Request $request, FormFactoryInterface $formFactory, Environment $twig): Response
    {
        $form = $formFactory->createNamed('profile', ProfileType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $document = $form->get('document')->getData();
            \assert($document instanceof UploadedFile);
            $stream = $document->openStream();
            $content = (string) stream_get_contents($stream);
            fclose($stream);

            return new JsonResponse([
                'age' => $form->get('age')->getData(),
                'filename' => $document->getClientOriginalName(),
                'size' => $document->getSize(),
                'sha256' => hash('sha256', $content),
            ]);
        }

        return new Response(
            $twig->render('profile.html.twig', ['form' => $form->createView()]),
            $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK,
        );
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
            'secret' => 'S3CRET',
            'test' => true,
            'http_method_override' => false,
            'router' => ['utf8' => true],
            'csrf_protection' => true,
            'lock' => 'flock',
            'session' => ['storage_factory_id' => 'session.storage.factory.mock_file'],
        ]);

        $container->extension('twig', [
            'default_path' => '%kernel.project_dir%/tests/Fixtures/templates',
        ]);

        $container->extension('ux_upload', [
            'storage' => 'local',
            'temp_dir' => '%kernel.cache_dir%/uploads/tmp',
            'local_storage' => [
                'directory' => '%kernel.cache_dir%/uploads/storage',
            ],
            'chunk_size' => '64K',
            'allow_anonymous' => 'anonymous_disabled' !== $this->environment,
            'uploaders' => [
                'avatar' => [
                    'max_size' => '1K',
                    'allowed_types' => ['image/png'],
                ],
            ],
        ]);

        $container->services()
            ->set(RequestHeaderUploadContextResolver::class)->autowire()
            ->alias(UploadContextResolverInterface::class, RequestHeaderUploadContextResolver::class)->public();
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('@UXUploadBundle/config/routes.php')
            ->prefix('/_ux/upload', false);

        $routes->add('profile', '/profile')->controller('kernel::profile');
    }
}
