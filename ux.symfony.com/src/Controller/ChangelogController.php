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

use App\Service\Changelog\ChangelogProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ChangelogController extends AbstractController
{
    public function __construct(
        private readonly ChangelogProvider $changeLogProvider,
    ) {
    }

    #[Route('/changelog', name: 'app_changelog')]
    public function __invoke(): Response
    {
        $changelog = $this->changeLogProvider->getChangelog();

        return $this->render('changelog.html.twig', [
            'changelog' => $changelog,
        ]);
    }
}
