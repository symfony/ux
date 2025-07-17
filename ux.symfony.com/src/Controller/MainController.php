<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Model\RecipeFileTree;
use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function homepage(UxPackageRepository $packageRepository): Response
    {
        $packages = $packageRepository->findAll();

        return $this->render('main/homepage.html.twig', [
            'packages' => $packages,
            'recipeFileTree' => new RecipeFileTree(),
        ]);
    }

    #[Route(path: '/robots.txt', name: 'app_robots')]
    public function __invoke(Request $request): Response
    {
        $response = $this->render('robots.txt.twig');
        $response->headers->set('Content-Type', 'text/plain');

        return $response;
    }
}
