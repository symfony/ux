<?php

namespace App\Controller;

use App\Repository\ExampleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ExampleRepository $exampleRepository): Response
    {
        return $this->render('home.html.twig', [
            'examples_by_package' => $exampleRepository->findAllByPackage(),
        ]);
    }
}
