<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ux-twig-component', name: 'app_ux_twig_component_')]
final class TwigComponentController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('ux_twig_component/index.html.twig', [
            'controller_name' => 'TwigComponentController',
        ]);
    }
}
