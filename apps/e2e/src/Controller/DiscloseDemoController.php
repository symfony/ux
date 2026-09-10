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

use App\Repository\ClientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DiscloseDemoController extends AbstractController
{
    #[Route('/ux-disclose', name: 'app_ux_disclose')]
    public function index(Request $request, ClientRepository $clients): Response
    {
        $response = $this->render('ux_disclose/index.html.twig', [
            'clients' => $clients->findAll(),
        ]);

        // The rate limit subject factory reads this cookie, so each demo
        // visitor gets its own disclosure budget.
        if ($request->query->has('r')) {
            $response->headers->setCookie(new Cookie('ux_disclose_subject', (string) $request->query->get('r'), 0, '/', null, false, true));
        }

        return $response;
    }
}
