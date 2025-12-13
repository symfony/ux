<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ux-notify', name: 'app_ux_notify_')]
final class NotifyController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('ux_notify/index.html.twig', [
            'controller_name' => 'NotifyController',
        ]);
    }
}
