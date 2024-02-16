<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\Changelog;

use Psr\Cache\CacheItemInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ChangelogProvider
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
    ) {
    }

    public function getChangelog(int $page = 1): array
    {
        $changelog = [];

        foreach ($this->loadReleases($page) as $release) {
            $changelog[] = $release;
        }
        return $changelog;
    }

    private function loadReleases(int $page = 1): array
    {
        return $this->cache->get('releases-symfony-ux-'.$page, function (CacheItemInterface $item) use ($page) {
            $item->expiresAfter(3600);

            return $this->getReleases('symfony', 'ux', $page, 20);
        });
    }

    /**
     * @return array<int, array{id: int, name: string, version: string, date: string, body: string}>
     */
    public function getReleases(string $owner, string $repo, int $page = 1, int $perPage = 50): array
    {
        $response = $this->httpClient->request('GET', sprintf('https://api.github.com/repos/%s/%s/releases', $owner, $repo), [
            'query' => [
                'page' => $page,
                'per_page' => $perPage,
            ],
        ]);

        $releases = [];
        foreach ($response->toArray() as $release) {
            $releases[$release['id']] = [
                'id' => $release['id'],
                'name' => $release['name'],
                'version' => $release['tag_name'],
                'date' => $release['published_at'],
                'body' => $release['body'],
            ];
        }

        // See headers links for pagination (?)
        // $response->getHeaders()['link'][0];

        return $releases;
    }
}
