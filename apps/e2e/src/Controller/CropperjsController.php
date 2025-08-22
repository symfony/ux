<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ux-cropperjs')]
final class CropperjsController extends AbstractController
{
    #[Route('/')]
    public function index(): Response
    {
        return $this->render('ux_cropperjs/index.html.twig', [
            'controller_name' => 'CropperjsController',
        ]);
    }
}
