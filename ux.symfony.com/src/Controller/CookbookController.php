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

use App\Service\CookbookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

class CookbookController extends AbstractController
{
    public function __construct(
        private readonly CookbookRepository $cookbookRepository,
    ) {
    }

    #[Route('/cookbook', name: 'app_cookbook')]
    public function index(): Response
    {
        $cookbooks = $this->cookbookRepository->findAll();

        return $this->render('cookbook/index.html.twig', [
            'cookbooks' => $cookbooks,
        ]);
    }

    #[Route('/cookbook/{slug}', name: 'app_cookbook_show', requirements: ['slug' => Requirement::ASCII_SLUG])]
    public function show(string $slug): Response
    {
        $cookbook = $this->cookbookRepository->findOneBySlug($slug);
        if (!$cookbook) {
            throw $this->createNotFoundException(\sprintf('Cookbook "%s" not found', $slug));
        }

        return $this->render('cookbook/show.html.twig', [
            'cookbook' => $cookbook,
        ]);
    }
}
