<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Toolkit;

use App\Enum\ToolkitKitId;
use App\Service\Toolkit\ToolkitService;
use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class KitsController extends AbstractController
{
    #[Route('/toolkit/kits')]
    public function listKits(): Response
    {
        return $this->redirectToRoute('app_toolkit', ['_fragment' => 'kits']);
    }

    #[Route('/toolkit/kits/{kitId}', name: 'app_toolkit_kit')]
    public function showKit(ToolkitKitId $kitId, ToolkitService $toolkitService, UxPackageRepository $uxPackageRepository): Response
    {
        $kit = $toolkitService->getKit($kitId);
        $package = $uxPackageRepository->find('toolkit');

        return $this->render('toolkit/kit.html.twig', [
            'package' => $package,
            'kit' => $kit,
            'kit_id' => $kitId,
            'components' => $toolkitService->getDocumentableComponents($kit),
        ]);
    }
}
