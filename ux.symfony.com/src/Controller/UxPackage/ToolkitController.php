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

use App\Service\Toolkit\ToolkitService;
use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ToolkitController extends AbstractController
{
    #[Route('/toolkit', name: 'app_toolkit')]
    public function index(
        UxPackageRepository $packageRepository,
        ToolkitService $toolkitService,
    ): Response {
        $package = $packageRepository->find('toolkit');

        return $this->render('ux_packages/toolkit.html.twig', [
            'package' => $package,
            'kits' => $toolkitService->getKits(),
        ]);
    }
}
