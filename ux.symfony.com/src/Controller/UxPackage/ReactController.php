<?php

namespace App\Controller\UxPackage;

use App\Service\UxPackageDataProvider;
use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReactController extends AbstractController
{
    #[Route('/react', name: 'app_react')]
    public function __invoke(UxPackageRepository $packageRepository, UxPackageDataProvider $packageDataProvider): Response
    {
        $package = $packageRepository->find('react');

        return $this->render('ux_packages/react.html.twig', [
            'package' => $package,
            'packagesData' => $packageDataProvider->getPackages(),
        ]);
    }
}
