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

use Psr\Cache\CacheItemInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ChangelogController extends AbstractController
{
    public function __construct(
        private readonly CacheInterface $cacheItem,
        private readonly HttpClientInterface $httpClient,
    )
    {
    }

    #[Route('/changelog', name: 'app_changelog')]
    public function __invoke(): Response
    {
        $changelog = $this->cacheItem->get('app.changelog', function (CacheItemInterface $item) {
            $item->expiresAfter(3600);

            return $this->getChangelog();
        });

        return $this->render('changelog.html.twig', [
            'changelog' => $changelog,
        ]);
    }

    private function getChangelog(): array
    {
        $response = $this->httpClient->request('GET', 'https://api.github.com/repos/symfony/ux/releases')->toArray();

        $changelog = [];
        foreach ($response as $release) {
            $changelog[$release['id']] = [
                'name' => $release['name'],
                'version' => $release['tag_name'],
                'date' => $release['published_at'],
                'body' => $release['body'],
            ];
        }
        return $changelog;
    }
}
