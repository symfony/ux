<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ux-dropzone', name: 'app_ux_dropzone_')]
final class DropzoneController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('ux_dropzone/index.html.twig', [
            'controller_name' => 'DropzoneController',
        ]);
    }
}
