<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\UxPackage;

use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SwupController extends AbstractController
{
    private const int PER_PAGE = 4;

    #[Route('/swup/{page<\d+>}', name: 'app_swup')]
    public function __invoke(UxPackageRepository $packageRepository, int $page = 1): Response
    {
        $package = $packageRepository->find('swup');

        $packages = $packageRepository->findAll();
        $pages = ceil(count($packages) / self::PER_PAGE);
        if ($page < 1 || $page > $pages) {
            throw $this->createNotFoundException('Page not found');
        }
        $results = array_slice($packages, ($page - 1) * self::PER_PAGE, self::PER_PAGE);

        return $this->render('ux_packages/swup.html.twig', [
            'package' => $package,
            'results' => $results,
            'page' => $page,
            'pages' => $pages,
        ]);
    }
}
