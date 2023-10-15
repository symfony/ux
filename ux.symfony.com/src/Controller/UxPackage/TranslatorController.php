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
use Symfony\Component\Routing\Annotation\Route;

class TranslatorController extends AbstractController
{
    #[Route('/translator', name: 'app_translator')]
    public function __invoke(UxPackageRepository $packageRepository): Response
    {
        $package = $packageRepository->find('translator');

        return $this->render('ux_packages/translator.html.twig', [
            'package' => $package,
        ]);
    }
}
