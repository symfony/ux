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

use App\Service\CookbookFactory;
use App\Service\CookbookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CookbookController extends AbstractController
{
    public function __construct(
        private CookbookRepository $cookbookRepository,
        private CookbookFactory $cookbookFactory,
    ) {
    }

    #[Route('/cookbook', name: 'app_cookbook_index')]
    public function index(): Response
    {
        $cookbooks = $this->cookbookRepository->findAll();

        return $this->render('cookbook/index.html.twig', [
            'cookbooks' => $cookbooks,
        ]);
    }

    #[Route('/cookbook/{slug}', name: 'app_cookbook_show')]
    public function show(string $slug): Response
    {
        $cookbook = $this->cookbookRepository->findOneByName($slug);

        return $this->render('cookbook/show.html.twig', [
            'slug' => $slug,
            'cookbook' => $cookbook,
        ]);
    }
}
