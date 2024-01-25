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
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\UX\Icons\Exception\IconNotFoundException;
use Symfony\UX\Icons\Svg\Icon;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class Iconify
{
    private HttpClientInterface $http;

    public function __construct(
        string $endpoint = 'https://api.iconify.design',
        ?HttpClientInterface $http = null,
    ) {
        if (!class_exists(HttpClient::class)) {
            throw new \LogicException('You must install "symfony/http-client" to use Iconify. Try running "composer require symfony/http-client".');
        }

        $this->http = ScopingHttpClient::forBaseUri($http ?? HttpClient::create(), $endpoint);
    }

    public function metadataFor(string $prefix): array
    {
        $response = $this->http->request('GET', sprintf('/collections?prefix=%s', $prefix));

        return $response->toArray()[$prefix] ?? throw new \RuntimeException(sprintf('The icon prefix "%s" does not exist on iconify.design.', $prefix));
    }

    public function fetchIcon(string $prefix, string $name): Icon
    {
        $response = $this->http->request('GET', sprintf('/%s.json?icons=%s', $prefix, $name));

        try {
            $data = $response->toArray();
        } catch (JsonException) {
            throw new IconNotFoundException(sprintf('The icon "%s:%s" does not exist on iconify.design.', $prefix, $name));
        }

        if (!isset($data['icons'][$name]['body'])) {
            throw new IconNotFoundException(sprintf('The icon "%s:%s" does not exist on iconify.design.', $prefix, $name));
        }

        return new Icon($data['icons'][$name]['body'], [
            'viewBox' => sprintf('0 0 %s %s', $data['width'], $data['height']),
        ]);
    }

    public function fetchSvg(string $prefix, string $name): string
    {
        $content = $this->http
            ->request('GET', sprintf('/%s/%s.svg', $prefix, $name))
            ->getContent()
        ;

        if (!str_starts_with($content, '<svg')) {
            throw new IconNotFoundException(sprintf('The icon "%s:%s" does not exist on iconify.design.', $prefix, $name));
        }

        return $content;
    }
}
