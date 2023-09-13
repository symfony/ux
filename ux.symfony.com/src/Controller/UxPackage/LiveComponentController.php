<?php

namespace App\Controller\UxPackage;

use App\Service\LiveDemoRepository;
use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LiveComponentController extends AbstractController
{
    #[Route('/live-component', name: 'app_live_component')]
    public function __invoke(UxPackageRepository $packageRepository, LiveDemoRepository $liveDemoRepository): Response
    {
        $package = $packageRepository->find('live-component');

        return $this->render('ux_packages/live_component.html.twig', [
            'package' => $package,
            'demos' => $liveDemoRepository->findAll(),
        ]);
    }

    #[Route('/live-component/demos/{demo}', name: 'app_live_component_demo_redirect')]
    public function redirectDemo(LiveDemoRepository $liveDemoRepository, string $demo = null): Response
    {
        if (null === $demo || '' === $demo) {
            return $this->redirectToRoute('app_demos');
        }

        // Permanent Redirect old URL
        if (null !== $liveDemo = $liveDemoRepository->find($demo)) {
            return $this->redirectToRoute($liveDemo->getRoute(), [], 301);
        }

        throw $this->createNotFoundException(sprintf('Live Component demo "%s" not found.', $demo));
    }
}
