<?php

namespace App\Service\Icon;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Iconify
{
    public function __construct(
        private HttpClientInterface $http,
        private CacheInterface $cache,
    ) {
    }

    public function search(string $query, ?string $prefix = null, int $limit = 32, ?int $start = null): array
    {
        return $this->http
            ->request('GET', 'https://api.iconify.design/search', [
                'query' => array_filter([
                    'query' => $query,
                    'limit' => $limit,
                    'prefix' => $prefix,
                    'start' => $start,
                ]),
            ])
            ->toArray()
        ;
    }

    public function collection(string $name): ?array
    {
        return $this->collectionData($name);
    }

    public function collectionStyles(string $prefix): array
    {
        $data = $this->collectionData($prefix);
        $styles = [];
        $styles['prefixes'] = $data['prefixes'] ?? [];
        $styles['suffixes'] = $data['suffixes'] ?? [];

        return $styles;
    }

    public function collectionCategories(string $prefix): array
    {
        $data = $this->collectionData($prefix);
        $categories = [];
        if (isset($data['uncategorized'])) {
            $categories['uncategorized'] = count($data['uncategorized']);
        }
        if (isset($data['categories'])) {
            foreach ($data['categories'] as $category => $categoryIcons) {
                $categories[$category] = count($categoryIcons);
            }
        }
        return $categories;
    }

    private function collectionData(string $prefix): array
    {
        return $this->cache->get('iconify-collection-'.$prefix, function (ItemInterface $item) use ($prefix) {
            $item->expiresAfter(604800); // 1 week

            return $this->http
                ->request('GET', 'https://api.iconify.design/collection', [
                    'query' => ['prefix' => $prefix, 'info' => 'true'],
                ])
                ->toArray();
        });
    }

    public function collectionIcons(string $prefix): array
    {
        $icons = [];
        $data = $this->collectionData($prefix);

        if (isset($data['uncategorized'])) {
            $icons = array_fill_keys($data['uncategorized'], true);
        }

        if (isset($data['categories'])) {
            foreach ($data['categories'] as $categoryIcons) {
                $icons = [...$icons, ...array_fill_keys($categoryIcons, true)];
            }
        }

        return array_keys($icons);
    }

    public function collections(): array
    {
        return $this->cache->get('iconify-collections', function (ItemInterface $item) {
            $item->expiresAfter(604800); // 1 week

            return $this->http->request('GET', 'https://api.iconify.design/collections')
                ->toArray()
            ;
        });
    }

    public function svg(string $prefix, string $name): ?string
    {
        return $this->cache->get("iconify-svg-{$prefix}-{$name}", function (ItemInterface $item) use ($prefix, $name) {
            $item->expiresAfter(604800); // 1 week

            $svg = $this->http->request('GET', "https://api.iconify.design/{$prefix}/{$name}.svg")
                ->getContent()
            ;

            return str_contains($svg, '<svg') ? $svg : null;
        });
    }
}
