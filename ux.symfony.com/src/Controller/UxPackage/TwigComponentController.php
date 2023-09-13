<?php

namespace App\Controller\UxPackage;

use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TwigComponentController extends AbstractController
{
    #[Route('/twig-component', name: 'app_twig_component')]
    public function __invoke(UxPackageRepository $packageRepository): Response
    {
        $package = $packageRepository->find('twig-component');

        return $this->render('ux_packages/twig-component.html.twig', [
            'package' => $package,
        ]);
    }
}
