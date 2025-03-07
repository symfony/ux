<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\UxPackage;

use App\Service\ToolkitComponentService;
use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Toolkit\Registry\Registry;
use Symfony\UX\Toolkit\Registry\RegistryItemType;

class ToolkitController extends AbstractController
{
    #[Route('/toolkit', name: 'app_toolkit')]
    public function index(UxPackageRepository $packageRepository, Registry $registry): Response
    {
        $package = $packageRepository->find('toolkit');
        return $this->render('ux_packages/toolkit.html.twig', [
            'components' => $registry->all(),
            'package' => $package,
        ]);
    }

    #[Route('/components/{currentComponent}', name: 'app_toolkit_components', defaults: ['currentComponent' => ''])]
    public function components(
        UxPackageRepository $packageRepository, 
        ToolkitComponentService $toolkitComponentService,
        string $currentComponent
    ): Response
    {
        $package = $packageRepository->find('toolkit');
        $registry = $toolkitComponentService->getRegistry();

        $component = $registry->get($currentComponent);
        if (null == $component) {
            // get the first non-example component
            $components = array_filter($registry->all(), function ($component) {
                if($component->type !== RegistryItemType::Component) {
                    return null;
                }

                return $component;
            });
            $component = reset($components);

            
        }

        if (null === $component) {
            throw $this->createNotFoundException('No component found');
        }

        // get all examples for this component
        $examples = array_filter($registry->all(), function ($component) use ($currentComponent) {
            if($component->type !== RegistryItemType::Example) {
                return null;
            }

            if ($component->parentName !== $currentComponent) {
                return null;
            }

            return $component;
        });

        return $this->render('ux_packages/toolkit/components.html.twig', [
            'components' => $registry->all(),
            'currentComponent' => $component,
            'examples' => $examples,
            'package' => $package,
        ]);
    }

    #[Route('/components/{currentComponent}/preview', name: 'app_toolkit_component_preview')]
    public function componentPreview(
        ToolkitComponentService $toolkitComponentService,
        string $currentComponent,
    ): Response
    {
        $currentComponentFromRegistry = $toolkitComponentService->getRegistry()->get($currentComponent, RegistryItemType::Example);
        if (null === $currentComponentFromRegistry) {
            throw $this->createNotFoundException('Example not found');
        }

        $html = $toolkitComponentService->preview($currentComponentFromRegistry->name, RegistryItemType::Example);
        return $this->render('ux_packages/toolkit/preview.html.twig', [
            'component' => $currentComponentFromRegistry,
            'html' => $html,
            // in the future, we'll change the framework  dynamically
            'cssFramework' => 'tailwindcss',
        ]);
    }

}
