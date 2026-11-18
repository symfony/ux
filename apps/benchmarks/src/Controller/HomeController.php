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
use Symfony\Component\Routing\RouterInterface;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(RouterInterface $router): Response
    {
        $paths = [];
        foreach ($router->getRouteCollection() as $name => $route) {
            if ('home' === $name || str_starts_with($name, '_') || str_starts_with($name, 'ux_')) {
                continue;
            }

            $paths[$name] = $route->getPath();
        }
        ksort($paths);

        return $this->render('home/index.html.twig', ['paths' => $paths]);
    }
}
