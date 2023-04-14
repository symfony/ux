<?php

namespace App\Controller;

use App\Service\LiveDemoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LiveComponentController extends AbstractController
{
    #[Route('/live-component', name: 'app_live_component')]
    public function liveComponent(LiveDemoRepository $liveDemoRepository): Response
    {
        return $this->render('live_component/live_component.html.twig', [
            'demos' => $liveDemoRepository->findAll(),
        ]);
    }
}
