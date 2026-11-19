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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/live')]
final class LiveComponentController extends AbstractController
{
    #[Route('/dashboard', name: 'live_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        return $this->render('live/dashboard.html.twig');
    }

    #[Route('/props', name: 'live_props', methods: ['GET'])]
    public function props(): Response
    {
        return $this->render('live/props.html.twig');
    }
}
