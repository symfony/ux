<?php

namespace App\Controller;

use App\Model\RecipeFileTree;
use App\Service\PackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function homepage(PackageRepository $packageRepository): Response
    {
        $packages = $packageRepository->findAll();

        return $this->render('main/homepage.html.twig', [
            'packages' => $packages,
            'recipeFileTree' => new RecipeFileTree(),
        ]);
    }

    #[Route('/components', name: 'app_all_components')]
    public function allComponents(PackageRepository $packageRepository): Response
    {
        $packages = $packageRepository->findAll();

        return $this->render('main/components.html.twig', [
            'packages' => $packages,
        ]);
    }
}
