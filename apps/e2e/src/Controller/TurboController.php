<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ux-turbo', name: 'app_ux_turbo_')]
final class TurboController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('ux_turbo/index.html.twig', [
            'controller_name' => 'TurboController',
        ]);
    }
}
