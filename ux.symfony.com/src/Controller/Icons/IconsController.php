<?php

namespace App\Controller\Icons;

use App\Service\Icon\IconSetRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class IconsController extends AbstractController
{
    #[Route('/icons', name: 'app_icons')]
    public function index(IconSetRepository $iconSetRepository): Response
    {
        // Homepage "Popular Icon Sets"
        // Hard-coded until we bring the IconSet's lists / views back
        $iconSets = [
            'bi',
            'bx',
            'flowbite',
            'iconoir',
            'lucide',
            'tabler',
        ];
        $iconSets = array_map(fn ($iconSet) => $iconSetRepository->find($iconSet), $iconSets);

        return $this->render('icons/index.html.twig', [
            'iconSets' => $iconSets,
        ]);
    }
}
