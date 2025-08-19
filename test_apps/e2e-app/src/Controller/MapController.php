<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ux-map')]
final class MapController extends AbstractController
{
    #[Route('/')]
    public function index(): Response
    {
        return $this->render('ux_map/index.html.twig', [
            'controller_name' => 'MapController',
        ]);
    }
}
