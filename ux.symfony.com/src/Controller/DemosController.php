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

use App\Service\LiveDemoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DemosController extends AbstractController
{
    #[Route('/demos', name: 'app_demos')]
    public function __invoke(LiveDemoRepository $liveDemoRepository): Response
    {
        return $this->render('main/demos.html.twig', [
            'liveComponentDemos' => $liveDemoRepository->findAll(),
        ]);
    }
}
