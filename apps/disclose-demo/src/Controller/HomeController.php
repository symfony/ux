<?php

namespace App\Controller;

use App\Repository\ClientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    private const int PAGE_SIZE = 6;

    #[Route('/', name: 'home')]
    public function index(Request $request, ClientRepository $clients): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $paginator = $clients->paginate($page, self::PAGE_SIZE);
        $pages = max(1, (int) ceil(count($paginator) / self::PAGE_SIZE));

        if ($page > $pages) {
            // The requested page is out of range (e.g. the last rows were
            // removed): fall back to the last page.
            $page = $pages;
            $paginator = $clients->paginate($page, self::PAGE_SIZE);
        }

        $response = $this->render('ux_disclose/index.html.twig', [
            'clients' => $paginator,
            'page' => $page,
            'pages' => $pages,
        ]);

        // The disclosure rate limiter keys on this cookie, so each visitor
        // gets an isolated budget across page reloads.
        if ($request->query->has('r')) {
            $response->headers->setCookie(new Cookie('ux_disclose_subject', (string) $request->query->get('r'), 0, '/', null, false, true));
        }

        return $response;
    }
}
