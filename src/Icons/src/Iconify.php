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
    private const ATTR_XMLNS_URL = 'https://www.w3.org/2000/svg';

    // URL must be 500 chars max (iconify limit)
    // -39 chars: https://api.iconify.design/XXX.json?icons=
    // -safe margin
    private const MAX_ICONS_QUERY_LENGTH = 400;

    private HttpClientInterface $http;
    private \ArrayObject $sets;
    private int $maxIconsQueryLength;

    public function __construct(
        private CacheInterface $cache,
        string $endpoint = self::API_ENDPOINT,
        ?HttpClientInterface $http = null,
        ?int $maxIconsQueryLength = null,
    ) {
        if (!class_exists(HttpClient::class)) {
            throw new \LogicException('You must install "symfony/http-client" to use Iconify. Try running "composer require symfony/http-client".');
        }

        $this->http = ScopingHttpClient::forBaseUri($http ?? HttpClient::create(), $endpoint);
        $this->maxIconsQueryLength = min(self::MAX_ICONS_QUERY_LENGTH, $maxIconsQueryLength ?? self::MAX_ICONS_QUERY_LENGTH);
    }

    public function metadataFor(string $prefix): array
    {
        return $this->sets()[$prefix] ?? throw new \RuntimeException(\sprintf('The icon prefix "%s" does not exist on iconify.design.', $prefix));
    }

    public function fetchIcon(string $prefix, string $name): Icon
    {
        if (!isset($this->sets()[$prefix])) {
            throw new IconNotFoundException(\sprintf('The icon "%s:%s" does not exist on iconify.design.', $prefix, $name));
        }

        $response = $this->http->request('GET', \sprintf('/%s.json?icons=%s', $prefix, $name));

        if (200 !== $response->getStatusCode()) {
            throw new IconNotFoundException(\sprintf('The icon "%s:%s" does not exist on iconify.design.', $prefix, $name));
        }

        try {
            $data = $response->toArray();
        } catch (JsonException) {
            throw new IconNotFoundException(\sprintf('The icon "%s:%s" does not exist on iconify.design.', $prefix, $name));
        }

        $nameArg = $name;
        if (isset($data['aliases'][$name])) {
            $name = $data['aliases'][$name]['parent'];
        }

        if (!isset($data['icons'][$name]['body'])) {
            throw new IconNotFoundException(\sprintf('The icon "%s:%s" does not exist on iconify.design.', $prefix, $nameArg));
        }

        $height = $data['icons'][$name]['height'] ?? $data['height'] ?? $this->sets()[$prefix]['height'] ?? null;
        $width = $data['icons'][$name]['width'] ?? $data['width'] ?? $this->sets()[$prefix]['width'] ?? null;
        if (null === $width && null === $height) {
            throw new \RuntimeException(\sprintf('The icon "%s:%s" does not have a width or height.', $prefix, $nameArg));
        }

        return new Icon($data['icons'][$name]['body'], [
            'xmlns' => self::ATTR_XMLNS_URL,
            'viewBox' => \sprintf('0 0 %s %s', $width ?? $height, $height ?? $width),
        ]);
    }

    public function fetchIcons(string $prefix, array $names): array
    {
        if (!isset($this->sets()[$prefix])) {
            throw new IconNotFoundException(\sprintf('The icon set "%s" does not exist on iconify.design.', $prefix));
        }

        // Sort to enforce cache hits
        sort($names);
        $queryString = implode(',', $names);
        if (!preg_match('#^[a-z0-9-,]+$#', $queryString)) {
            throw new \InvalidArgumentException('Invalid icon names.'.$queryString);
        }

        if (self::MAX_ICONS_QUERY_LENGTH < \strlen($prefix.$queryString)) {
            throw new \InvalidArgumentException('The query string is too long.');
        }

        $response = $this->http->request('GET', \sprintf('/%s.json', $prefix), [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'query' => [
                'icons' => strtolower($queryString),
            ],
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new IconNotFoundException(\sprintf('The icon set "%s" does not exist on iconify.design.', $prefix));
        }

        $data = $response->toArray();

        $icons = [];
        foreach ($names as $iconName) {
            $iconData = $data['icons'][$data['aliases'][$iconName]['parent'] ?? $iconName] ?? null;
            if (!$iconData) {
                continue;
            }

            $height = $iconData['height'] ?? $data['height'] ??= $this->sets()[$prefix]['height'] ?? null;
            $width = $iconData['width'] ?? $data['width'] ??= $this->sets()[$prefix]['width'] ?? null;

            $icons[$iconName] = new Icon($iconData['body'], [
                'xmlns' => self::ATTR_XMLNS_URL,
                'viewBox' => \sprintf('0 0 %d %d', $width ?? $height, $height ?? $width),
            ]);
        }

        return $icons;
    }

    public function hasIconSet(string $prefix): bool
    {
        return isset($this->sets()[$prefix]);
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

    /**
     * @return iterable<string[]>
     */
    public function chunk(string $prefix, array $names): iterable
    {
        if (100 < ($prefixLength = \strlen($prefix))) {
            throw new \InvalidArgumentException(\sprintf('The icon prefix "%s" is too long.', $prefix));
        }

        $maxLength = $this->maxIconsQueryLength - $prefixLength;

        $curBatch = [];
        $curLength = 0;
        foreach ($names as $name) {
            if (100 < ($nameLength = \strlen($name))) {
                throw new \InvalidArgumentException(\sprintf('The icon name "%s" is too long.', $name));
            }
            if ($curLength && ($maxLength < ($curLength + $nameLength + 1))) {
                yield $curBatch;

                $curBatch = [];
                $curLength = 0;
            }
            $curLength += $nameLength + 1;
            $curBatch[] = $name;
        }

        if ($curLength) {
            yield $curBatch;
        }

        yield from [];
    }

    private function sets(): \ArrayObject
    {
        return $this->sets ??= $this->cache->get('iconify-sets', function () {
            $response = $this->http->request('GET', '/collections');

            return new \ArrayObject($response->toArray());
        });
    }
}
