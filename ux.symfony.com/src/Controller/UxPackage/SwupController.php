<?php

namespace App\Controller\UxPackage;

use App\Service\UxPackageRepository;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SwupController extends AbstractController
{
    #[Route('/swup/{page<\d+>}', name: 'app_swup')]
    public function __invoke(UxPackageRepository $packageRepository, int $page = 1): Response
    {
        $package = $packageRepository->find('swup');

        $pagerfanta = Pagerfanta::createForCurrentPageWithMaxPerPage(
            new ArrayAdapter($packageRepository->findAll()),
            $page,
            4
        );

        return $this->render('ux_packages/swup.html.twig', [
            'package' => $package,
            'pager' => $pagerfanta,
        ]);
    }
}
