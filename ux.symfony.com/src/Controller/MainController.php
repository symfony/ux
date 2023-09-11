<?php

namespace App\Controller;

use App\Model\RecipeFileTree;
use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function homepage(UxPackageRepository $packageRepository): Response
    {
        $packages = $packageRepository->findAll();

        return $this->render('main/homepage.html.twig', [
            'packages' => $packages,
            'recipeFileTree' => new RecipeFileTree(),
        ]);
    }
}
