<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Demo;

use App\LiveMemory\GameStorageInterface;
use App\Service\LiveDemoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/demos/live-memory')]
class LiveMemoryController extends AbstractController
{
    #[Route('/', name: 'app_demo_live_memory')]
    #[Route('/', name: 'app_demo_live_component_live_memory')]
    public function __invoke(LiveDemoRepository $liveDemoRepository): Response
    {
        return $this->render('demos/live_memory/index.html.twig', [
            'demo' => $liveDemoRepository->find('live-memory'),
        ]);
    }

    /**
     * In dev environment, this route allows you to remove the saved game
     * from the session. This can be useful when you want to start over.
     */
    #[Route('/reset', name: 'app_demo_live_memory_reset', env: 'dev')]
    #[Route('/reset', name: 'app_demo_live_component_live_memory_reset', env: 'dev')]
    public function reset(GameStorageInterface $gameStorage): Response
    {
        $gameStorage->saveGame(null);

        return $this->redirectToRoute('app_demo_live_memory');
    }
}
