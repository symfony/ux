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

use App\Data\IconNames;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/icons')]
final class IconsController extends AbstractController
{
    #[Route('/grid', name: 'icons_grid', methods: ['GET'])]
    public function grid(IconNames $icons): Response
    {
        return $this->render('icons/grid.html.twig', [
            'names' => $icons->all(),
            'repeat' => IconNames::REPEAT,
        ]);
    }
}
