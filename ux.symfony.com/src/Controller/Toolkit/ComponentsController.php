<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Toolkit;

use App\Enum\ToolkitKit;
use App\Service\Toolkit\ToolkitService;
use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Toolkit\File\FileType;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentTemplateFinderInterface;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;

class ComponentsController extends AbstractController
{
    public function __construct(
        private ToolkitService $toolkitService,
        private UxPackageRepository $uxPackageRepository,
    ) {
    }

    #[Route('/toolkit/kits/{kit}/components/')]
    public function listComponents(ToolkitKit $kit): Response
    {
        // TODO: implementing listing in the future :D

        return $this->redirectToRoute('app_toolkit_kit', [
            'kit' => $kit->value,
        ], Response::HTTP_FOUND);
    }

    #[Route('/toolkit/kits/{kit}/components/{componentName}', name: 'app_toolkit_component')]
    public function showComponent(ToolkitKit $kit, string $componentName): Response
    {
        if (null === $component = $this->toolkitService->getComponent($kit, $componentName)) {
            throw $this->createNotFoundException(\sprintf('Component "%s" not found', $componentName));
        }

        $package = $this->uxPackageRepository->find('toolkit');

        return $this->render('toolkit/component.html.twig', [
            'package' => $package,
            'kit' => $kit,
            'components' => $this->toolkitService->getDocumentableComponents($kit),
            'component' => $component,
        ]);
    }

    #[Route('/toolkit/component_preview', name: 'app_toolkit_component_preview')]
    public function previewComponent(
        Request $request,
        #[MapQueryParameter] ToolkitKit $toolkitKit,
        #[MapQueryParameter] string $code,
        #[MapQueryParameter] string $height,
        UriSigner $uriSigner,
        \Twig\Environment $twig,
        #[Autowire(service: 'ux.twig_component.component_factory')]
        ComponentFactory $componentFactory,
        #[Autowire(service: 'profiler')]
        ?Profiler $profiler,
    ): Response {
        if (!$uriSigner->checkRequest($request)) {
            throw new BadRequestHttpException('Request is invalid.');
        }

        $profiler?->disable();

        $kit = $this->toolkitService->getKit($toolkitKit);

        $twig->setLoader(new ChainLoader([
            new FilesystemLoader($kit->path.\DIRECTORY_SEPARATOR.'templates'.\DIRECTORY_SEPARATOR.'components'),
            $twig->getLoader(),
        ]));

        $this->tweakComponentFactory(
            $componentFactory,
            new class($kit) implements ComponentTemplateFinderInterface {
                public function __construct(
                    private readonly Kit $kit,
                ) {
                }

                public function findAnonymousComponentTemplate(string $name): ?string
                {
                    if ($component = $this->kit->getComponent($name)) {
                        foreach ($component->files as $file) {
                            if (FileType::Twig === $file->type) {
                                return $file->relativePathName;
                            }
                        }
                    }

                    return null;
                }
            }
        );

        $template = $twig->createTemplate(<<<HTML
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Preview</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        {{ importmap('toolkit-{$toolkitKit->value}') }}
    </head>
    <body class="flex min-h-[{$height}] w-full justify-center p-5 items-center">{$code}</body>
</html>
HTML);

        return new Response(
            $twig->render($template),
            Response::HTTP_OK,
            ['X-Robots-Tag' => 'noindex, nofollow']
        );
    }

    /**
     * Tweak the ComponentFactory to render anonymous components from the Toolkit kit.
     * TODO: In the future, we should implement multiple directories for anonymous components.
     */
    private function tweakComponentFactory(ComponentFactory $componentFactory, ComponentTemplateFinderInterface $componentTemplateFinder): void
    {
        $refl = new \ReflectionClass($componentFactory);

        $propertyConfig = $refl->getProperty('config');
        $propertyConfig->setValue($componentFactory, []);

        $propertyComponentTemplateFinder = $refl->getProperty('componentTemplateFinder');
        $propertyComponentTemplateFinder->setValue($componentFactory, $componentTemplateFinder);
    }
}
