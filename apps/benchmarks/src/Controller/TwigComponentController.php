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

#[Route('/twig-component')]
final class TwigComponentController extends AbstractController
{
    #[Route('/dashboard', name: 'twig_component_dashboard', methods: ['GET'])]
    public function dashboard(DashboardData $data): Response
    {
        return $this->render('twig_component/dashboard.html.twig', $data->forRows(DashboardData::TWIG_COMPONENT_ROWS));
    }
}
