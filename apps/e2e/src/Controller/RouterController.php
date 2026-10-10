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

#[Route('/ux-router', name: 'app_ux_router_')]
final class RouterController extends AbstractController
{
    #[Route('/basic', name: 'basic')]
    public function basic(): Response
    {
        return $this->render('ux_router/basic.html.twig');
    }

    #[Route('/blog/{slug}', name: 'blog_show')]
    public function blogShow(string $slug): Response
    {
        return $this->render('ux_router/blog_show.html.twig', ['slug' => $slug]);
    }

    #[Route(['en' => '/about', 'fr' => '/a-propos'], name: 'about')]
    public function about(): Response
    {
        return $this->render('ux_router/about.html.twig');
    }
}
