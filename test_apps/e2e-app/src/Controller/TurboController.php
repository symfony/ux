<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ux-turbo')]
final class TurboController extends AbstractController
{
    #[Route('/')]
    public function index(): Response
    {
        return $this->render('ux_turbo/index.html.twig', [
            'controller_name' => 'TurboController',
        ]);
    }
}
