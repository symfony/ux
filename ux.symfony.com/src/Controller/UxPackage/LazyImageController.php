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

class LazyImageController extends AbstractController
{
    #[Route('/lazy-image', name: 'app_lazy_image')]
    public function __invoke(UxPackageRepository $packageRepository): Response
    {
        $package = $packageRepository->find('lazy-image');
        $legosFilePath = $this->getParameter('kernel.project_dir').'/assets/images/legos.jpg';

        return $this->render('ux_packages/lazy_image.html.twig', [
            'package' => $package,
            'legosFilePath' => $legosFilePath,
        ]);
    }
}
