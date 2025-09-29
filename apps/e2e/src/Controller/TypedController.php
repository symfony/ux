<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ux-typed')]
final class TypedController extends AbstractController
{
    #[Route('/')]
    public function index(): Response
    {
        return $this->render('ux_typed/index.html.twig', [
            'controller_name' => 'TypedController',
        ]);
    }
}
