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

class StimulusController extends AbstractController
{
    public function __construct(
        private readonly UxPackageRepository $packageRepository,
    ) {
    }

    #[Route('/stimulus', name: 'app_stimulus')]
    public function __invoke(): Response
    {
        $package = $this->packageRepository->find('stimulus');

        return $this->render('ux_packages/stimulus.html.twig', [
            'package' => $package,
        ]);
    }
}
