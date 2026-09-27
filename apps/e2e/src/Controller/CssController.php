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
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ux-css', name: 'app_ux_css_')]
final class CssController extends AbstractController
{
    #[Route('/basic', name: 'basic')]
    public function basic(Request $request): Response
    {
        $tone = 'primary' === $request->query->get('tone') ? 'primary' : 'danger';

        return $this->render('ux_css/basic.html.twig', ['tone' => $tone]);
    }
}
