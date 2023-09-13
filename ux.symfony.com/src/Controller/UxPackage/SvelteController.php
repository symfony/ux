<?php

namespace App\Controller\UxPackage;

use App\Service\UxPackageDataProvider;
use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SvelteController extends AbstractController
{
    #[Route('/svelte', name: 'app_svelte')]
    public function __invoke(UxPackageRepository $packageRepository, UxPackageDataProvider $packageDataProvider): Response
    {
        $package = $packageRepository->find('svelte');

        return $this->render('ux_packages/svelte.html.twig', [
            'package' => $package,
            'packagesData' => $packageDataProvider->getPackages(),
        ]);
    }
}
