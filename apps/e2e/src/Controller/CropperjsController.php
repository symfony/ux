<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ux-cropperjs', name: 'app_ux_cropperjs_')]
final class CropperjsController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('ux_cropperjs/index.html.twig', [
            'controller_name' => 'CropperjsController',
        ]);
    }
}
