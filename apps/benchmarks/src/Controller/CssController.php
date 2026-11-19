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

use App\Data\DashboardData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/css')]
final class CssController extends AbstractController
{
    #[Route('/tailwind', name: 'css_tailwind', methods: ['GET'])]
    public function tailwind(DashboardData $data): Response
    {
        return $this->render('css/tailwind.html.twig', $data->forRows(DashboardData::CSS_ROWS));
    }

    #[Route('/ux-css', name: 'css_ux_css', methods: ['GET'])]
    public function uxCss(DashboardData $data): Response
    {
        return $this->render('css/ux_css.html.twig', $data->forRows(DashboardData::CSS_ROWS));
    }
}
