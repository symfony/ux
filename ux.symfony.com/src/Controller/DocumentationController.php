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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DocumentationController extends AbstractController
{
    #[Route('/documentation', name: 'app_documentation')]
    public function __invoke(UxPackageRepository $packageRepository): Response
    {
        $packages = $packageRepository->findAll();
        usort($packages, fn ($a, $b) => $a->getHumanName() <=> $b->getHumanName());

        return $this->render('documentation/index.html.twig', [
            'packages' => $packages,
        ]);
    }
}
