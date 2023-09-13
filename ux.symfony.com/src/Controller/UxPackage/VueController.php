<?php

namespace App\Controller\UxPackage;

use App\Service\UxPackageDataProvider;
use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VueController extends AbstractController
{
    #[Route('/vue', name: 'app_vue')]
    public function __invoke(UxPackageRepository $packageRepository, UxPackageDataProvider $packageDataProvider): Response
    {
        $package = $packageRepository->find('vue');

        return $this->render('ux_packages/vue.html.twig', [
            'package' => $package,
            'packagesData' => $packageDataProvider->getPackages(),
        ]);
    }
}
