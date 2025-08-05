<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Util;

use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
class UrlFactory
{
    public function __construct(
        private RouterInterface $router,
    ) {
    }

    public function createFromPreviousAndProps(
        string $previousUrl,
        array $pathMappedProps,
        array $queryMappedProps,
    ): ?string {
        $parsed = parse_url($previousUrl);
        if (false === $parsed) {
            return null;
        }

        try {
            $newUrl = $this->createPath($parsed['path'] ?? '', $pathMappedProps);
        } catch (ResourceNotFoundException|MethodNotAllowedException|MissingMandatoryParametersException) {
            return null;
        }

        return $this->replaceQueryString(
            $newUrl,
            array_merge(
                $this->getPreviousQueryParameters($parsed['query'] ?? ''),
                $this->getRemnantProps($newUrl),
                $queryMappedProps,
            )
        );
    }

    private function createPath(string $previousUrl, array $props): string
    {
        $newPath = $this->router->generate(
            $this->matchRoute($previousUrl),
            $props
        );

        return $newPath;
    }

    private function matchRoute(string $previousUrl): string
    {
        $context = $this->router->getContext();
        $tmpContext = clone $context;
        $tmpContext->setMethod('GET');
        $this->router->setContext($tmpContext);
        try {
            $match = $this->router->match($previousUrl);
        } finally {
            $this->router->setContext($context);
        }

        return $match['_route'] ?? '';
    }

    private function replaceQueryString($url, array $props): string
    {
        $queryString = http_build_query($props);

        return preg_replace('/[?#].*/', '', $url).
            ('' !== $queryString ? '?' : '').
            $queryString;
    }

    /**
     * Keep the query parameters of the previous request.
     */
    private function getPreviousQueryParameters(string $query): array
    {
        parse_str($query, $previousQueryParams);

        return $previousQueryParams;
    }

    /**
     * Symfony router will set props in query if they do not match route parameter.
     */
    private function getRemnantProps(string $newUrl): array
    {
        parse_str(parse_url($newUrl)['query'] ?? '', $remnantQueryParams);

        return $remnantQueryParams;
    }
}
