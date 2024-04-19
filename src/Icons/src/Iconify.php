<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons;

use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\UX\Icons\Exception\IconNotFoundException;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class Iconify
{
    public const API_ENDPOINT = 'https://api.iconify.design';

    private HttpClientInterface $http;
    private \ArrayObject $sets;

    public function __construct(
        private CacheInterface $cache,
        string $endpoint = self::API_ENDPOINT,
        ?HttpClientInterface $http = null,
    ) {
        if (!class_exists(HttpClient::class)) {
            throw new \LogicException('You must install "symfony/http-client" to use Iconify. Try running "composer require symfony/http-client".');
        }

        $this->http = ScopingHttpClient::forBaseUri($http ?? HttpClient::create(), $endpoint);
    }

    public function metadataFor(string $prefix): array
    {
        return $this->sets()[$prefix] ?? throw new \RuntimeException(sprintf('The icon prefix "%s" does not exist on iconify.design.', $prefix));
    }

    public function fetchIcon(string $prefix, string $name): Icon
    {
        if (!isset($this->sets()[$prefix])) {
            throw new IconNotFoundException(sprintf('The icon "%s:%s" does not exist on iconify.design.', $prefix, $name));
        }

        $response = $this->http->request('GET', sprintf('/%s.json?icons=%s', $prefix, $name));

        try {
            $data = $response->toArray();
        } catch (JsonException) {
            throw new IconNotFoundException(sprintf('The icon "%s:%s" does not exist on iconify.design.', $prefix, $name));
        }

        $nameArg = $name;
        if (isset($data['aliases'][$name])) {
            $name = $data['aliases'][$name]['parent'];
        }

        if (!isset($data['icons'][$name]['body'])) {
            throw new IconNotFoundException(sprintf('The icon "%s:%s" does not exist on iconify.design.', $prefix, $nameArg));
        }

        $height = $data['icons'][$name]['height'] ?? $data['height'] ?? $this->sets()[$prefix]['height'] ?? null;
        $width = $data['icons'][$name]['width'] ?? $data['width'] ?? $this->sets()[$prefix]['width'] ?? null;
        if (null === $width && null === $height) {
            throw new \RuntimeException(sprintf('The icon "%s:%s" does not have a width or height.', $prefix, $nameArg));
        }

        return new Icon($data['icons'][$name]['body'], [
            'viewBox' => sprintf('0 0 %s %s', $width ?? $height, $height ?? $width),
        ]);
    }

    public function fetchSvg(string $prefix, string $name): string
    {
        if (!isset($this->sets()[$prefix])) {
            throw new IconNotFoundException(sprintf('The icon "%s:%s" does not exist on iconify.design.', $prefix, $name));
        }

        $content = $this->http
            ->request('GET', sprintf('/%s/%s.svg', $prefix, $name))
            ->getContent()
        ;

        if (!str_starts_with($content, '<svg')) {
            throw new IconNotFoundException(sprintf('The icon "%s:%s" does not exist on iconify.design.', $prefix, $name));
        }

        return $content;
    }

    public function getIconSets(): array
    {
        return $this->sets()->getArrayCopy();
    }

    public function searchIcons(string $prefix, string $query)
    {
        $response = $this->http->request('GET', '/search', [
            'query' => [
                'query' => $query,
                'prefix' => $prefix,
            ],
        ]);

        return new \ArrayObject($response->toArray());
    }

    private function sets(): \ArrayObject
    {
        return $this->sets ??= $this->cache->get('ux-iconify-sets', function () {
            $response = $this->http->request('GET', '/collections');

            return new \ArrayObject($response->toArray());
        });
    }
}
