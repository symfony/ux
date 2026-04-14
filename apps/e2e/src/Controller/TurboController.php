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

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Turbo\TurboBundle;

#[Route('/ux-turbo', name: 'app_ux_turbo_')]
final class TurboController extends AbstractController
{
    #[Route('/drive', name: 'drive')]
    public function drive(
        #[MapQueryParameter] int $page = 1,
    ): Response {
        if (2 === $page) {
            return $this->render('ux_turbo/drive_page_2.html.twig', [
                'current_time' => new \DateTimeImmutable()->format(\DateTimeInterface::RFC3339_EXTENDED),
            ]);
        }

        return $this->render('ux_turbo/drive.html.twig', [
            'current_time' => new \DateTimeImmutable()->format(\DateTimeInterface::RFC3339_EXTENDED),
        ]);
    }

    #[Route('/frame', name: 'frame')]
    public function frame(): Response
    {
        return $this->render('ux_turbo/frame.html.twig');
    }

    #[Route('/frame-content', name: 'frame_content')]
    public function frameContent(): Response
    {
        return $this->render('ux_turbo/frame_content.html.twig');
    }

    #[Route('/stream', name: 'stream')]
    public function streamAction(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->render('ux_turbo/stream_response.html.twig');
            }

            return $this->redirectToRoute('app_ux_turbo_stream');
        }

        return $this->render('ux_turbo/stream.html.twig');
    }
}
