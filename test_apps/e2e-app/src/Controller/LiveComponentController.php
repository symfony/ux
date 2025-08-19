<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ux-live-component')]
final class LiveComponentController extends AbstractController
{
    #[Route('/')]
    public function index(): Response
    {
        return $this->render('ux_live_component/index.html.twig', [
            'controller_name' => 'LiveComponentController',
        ]);
    }
}
