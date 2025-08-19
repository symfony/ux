<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ux-vue')]
final class VueController extends AbstractController
{
    #[Route('/')]
    public function index(): Response
    {
        return $this->render('ux_vue/index.html.twig', [
            'controller_name' => 'VueController',
        ]);
    }
}
