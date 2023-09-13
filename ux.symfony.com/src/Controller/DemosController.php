<?php

namespace App\Controller;

use App\Service\LiveDemoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DemosController extends AbstractController
{
    #[Route('/demos', name: 'app_demos')]
    public function __invoke(LiveDemoRepository $liveDemoRepository): Response
    {
        return $this->render('main/demos.html.twig', [
            'liveComponentDemos' => $liveDemoRepository->findAll(),
        ]);
    }
}
