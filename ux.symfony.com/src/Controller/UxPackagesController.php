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

use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UxPackagesController extends AbstractController
{
    #[Route('/packages', name: 'app_packages')]
    public function __invoke(UxPackageRepository $packageRepository): Response
    {
        $packages = $packageRepository->findAll();

        return $this->render('main/packages.html.twig', [
            'packages' => $packages,
        ]);
    }

    #[Route('/components')]
    public function componentsRedirect(): RedirectResponse
    {
        return $this->redirectToRoute('app_packages', [], Response::HTTP_MOVED_PERMANENTLY);
    }
}
