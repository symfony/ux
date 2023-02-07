<?php

namespace App\Controller;

use App\Service\PackageRepository;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class _SwupController extends AbstractController
{
    #[Route('/swup/{page<\d+>}', name: 'app_swup')]
    public function swup(PackageRepository $packageRepository, int $page = 1): Response
    {
        // this is a normal Symfony controller and pagination links are just an example:
        // swup works with any "a" tags, so you don't have to make any changes in your code.
        $pager = Pagerfanta::createForCurrentPageWithMaxPerPage(
            new ArrayAdapter($packageRepository->findAll()),
            $page,
            4
        );

        return $this->render('packages/index.html.twig', [
            'pager' => $pager,
        ]);
    }
}
