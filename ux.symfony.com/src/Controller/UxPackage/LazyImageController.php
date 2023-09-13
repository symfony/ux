<?php

namespace App\Controller\UxPackage;

use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LazyImageController extends AbstractController
{
    #[Route('/lazy-image', name: 'app_lazy_image')]
    public function __invoke(UxPackageRepository $packageRepository): Response
    {
        $package = $packageRepository->find('lazy-image');
        $legosFilePath = $this->getParameter('kernel.project_dir').'/assets/images/legos.jpg';

        return $this->render('ux_packages/lazy-image.html.twig', [
            'package' => $package,
            'legosFilePath' => $legosFilePath,
        ]);
    }
}
