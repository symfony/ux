<?php

namespace App\Controller\UxPackage;

use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TypedController extends AbstractController
{
    #[Route('/typed', name: 'app_typed')]
    public function __invoke(UxPackageRepository $packageRepository): Response
    {
        $package = $packageRepository->find('typed');

        return $this->render('ux_packages/typed.html.twig', [
            'package' => $package,
        ]);
    }
}
