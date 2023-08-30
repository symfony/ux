<?php

namespace App\Controller;

use App\Model\RecipeFileTree;
use App\Service\PackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
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

    #[Route('/packages', name: 'app_all_packages')]
    public function allPackages(PackageRepository $packageRepository): Response
    {
        $packages = $packageRepository->findAll();

        return $this->render('main/packages.html.twig', [
            'packages' => $packages,
        ]);
    }

    #[Route('/components')]
    public function componentsRedirect(): RedirectResponse
    {
        return $this->redirectToRoute('app_all_packages', [], Response::HTTP_MOVED_PERMANENTLY);
    }
}
