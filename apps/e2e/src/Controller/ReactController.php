<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ux-react')]
final class ReactController extends AbstractController
{
    #[Route('/')]
    public function index(): Response
    {
        return $this->render('ux_react/index.html.twig');
    }
}
