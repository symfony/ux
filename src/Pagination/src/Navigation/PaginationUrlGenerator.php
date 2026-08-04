<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Navigation;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Pagination\Exception\InvalidArgumentException;
use Symfony\UX\Pagination\Exception\RuntimeException;

/**
 * Generates pagination URLs from immutable routing and query-string options.
 *
 * Stores route, parameters and fragment, but generates nothing until a URL is
 * requested. This keeps URL composition immutable after construction.
 *
 * Supports path-based page parameters: a route like /blog/{page}
 * is auto-detected and pagination links keep the page in the path.
 *
 * @internal
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class PaginationUrlGenerator
{
    /**
     * @param array<string, mixed> $routeParams
     * @param array<string, mixed> $appends
     */
    public function __construct(
        private readonly string $queryParam = 'page',
        private readonly ?string $route = null,
        private readonly array $routeParams = [],
        private readonly array $appends = [],
        private readonly ?string $fragment = null,
        private readonly QueryStringPolicy $queryStringPolicy = QueryStringPolicy::Preserve,
        /** @var list<string> */
        private readonly array $excludedQueryParameters = [],
        private readonly ?string $basePath = null,
        private readonly ?RequestStack $requestStack = null,
        private readonly ?UrlGeneratorInterface $urlGenerator = null,
        private readonly string $cursorParam = 'cursor',
    ) {
    }

    public function getQueryParameterName(): string
    {
        return $this->queryParam;
    }

    public function getRouteName(): ?string
    {
        return $this->route;
    }

    public function getCursorParameterName(): string
    {
        return $this->cursorParam;
    }

    public function withQueryParameter(string $name): self
    {
        if ('' === $name) {
            throw new InvalidArgumentException('Query parameter name must not be empty.');
        }

        return new self(
            $name,
            $this->route,
            $this->routeParams,
            $this->appends,
            $this->fragment,
            $this->queryStringPolicy,
            $this->excludedQueryParameters,
            $this->basePath,
            $this->requestStack,
            $this->urlGenerator,
            $this->cursorParam,
        );
    }

    public function withCursorParameter(string $name): self
    {
        if ('' === $name) {
            throw new InvalidArgumentException('Cursor parameter name must not be empty.');
        }

        return new self(
            $this->queryParam,
            $this->route,
            $this->routeParams,
            $this->appends,
            $this->fragment,
            $this->queryStringPolicy,
            $this->excludedQueryParameters,
            $this->basePath,
            $this->requestStack,
            $this->urlGenerator,
            $name,
        );
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function withRoute(string $route, array $parameters = []): self
    {
        if ('' === $route) {
            throw new InvalidArgumentException('Route name must not be empty.');
        }

        return new self(
            $this->queryParam,
            $route,
            $parameters,
            $this->appends,
            $this->fragment,
            $this->queryStringPolicy,
            $this->excludedQueryParameters,
            null,
            $this->requestStack,
            $this->urlGenerator,
            $this->cursorParam,
        );
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function withQueryParameters(array $parameters): self
    {
        return new self(
            $this->queryParam,
            $this->route,
            $this->routeParams,
            array_merge($this->appends, $parameters),
            $this->fragment,
            $this->queryStringPolicy,
            $this->excludedQueryParameters,
            $this->basePath,
            $this->requestStack,
            $this->urlGenerator,
            $this->cursorParam,
        );
    }

    public function withQueryString(): self
    {
        return new self(
            $this->queryParam,
            $this->route,
            $this->routeParams,
            $this->appends,
            $this->fragment,
            QueryStringPolicy::Preserve,
            $this->excludedQueryParameters,
            $this->basePath,
            $this->requestStack,
            $this->urlGenerator,
            $this->cursorParam,
        );
    }

    public function withoutQueryString(): self
    {
        return new self(
            $this->queryParam,
            $this->route,
            $this->routeParams,
            $this->appends,
            $this->fragment,
            QueryStringPolicy::Discard,
            $this->excludedQueryParameters,
            $this->basePath,
            $this->requestStack,
            $this->urlGenerator,
            $this->cursorParam,
        );
    }

    public function withoutQueryParameters(string ...$names): self
    {
        foreach ($names as $name) {
            if ('' === $name) {
                throw new InvalidArgumentException('Query parameter names must not be empty.');
            }
        }

        return new self(
            $this->queryParam,
            $this->route,
            $this->routeParams,
            $this->appends,
            $this->fragment,
            $this->queryStringPolicy,
            array_values(array_unique([...$this->excludedQueryParameters, ...$names])),
            $this->basePath,
            $this->requestStack,
            $this->urlGenerator,
            $this->cursorParam,
        );
    }

    public function withFragment(string $fragment): self
    {
        return new self(
            $this->queryParam,
            $this->route,
            $this->routeParams,
            $this->appends,
            $fragment,
            $this->queryStringPolicy,
            $this->excludedQueryParameters,
            $this->basePath,
            $this->requestStack,
            $this->urlGenerator,
            $this->cursorParam,
        );
    }

    public function withPath(string $path): self
    {
        return new self(
            $this->queryParam,
            null,
            [],
            $this->appends,
            $this->fragment,
            $this->queryStringPolicy,
            $this->excludedQueryParameters,
            $path,
            $this->requestStack,
            $this->urlGenerator,
            $this->cursorParam,
        );
    }

    public function getUrl(int $page): string
    {
        if ($page < 1) {
            throw new InvalidArgumentException('Page must be greater than or equal to 1.');
        }

        return $this->generateUrl($this->buildPageParams($page));
    }

    /**
     * Generate an absolute URL (scheme and host included) for a page.
     *
     * Meant for contexts where relative URLs are fragile: feeds, emails,
     * HTTP link headers and application-defined metadata.
     */
    public function getAbsoluteUrl(int $page): string
    {
        if ($page < 1) {
            throw new InvalidArgumentException('Page must be greater than or equal to 1.');
        }

        return $this->generateUrl($this->buildPageParams($page), true);
    }

    /**
     * Generate a URL with a cursor parameter instead of a page parameter.
     */
    public function getCursorUrl(string $cursorValue): string
    {
        if ('' === $cursorValue) {
            throw new InvalidArgumentException('Cursor value must not be empty.');
        }

        return $this->generateUrl($this->buildCursorParams($cursorValue));
    }

    // -------------------------------------------------------------------------
    // Param builders
    // -------------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function buildPageParams(int $page): array
    {
        $params = $this->buildBaseParams();
        unset($params[$this->cursorParam]);

        // Add page param (omit for page 1)
        if (1 !== $page) {
            $params[$this->queryParam] = $page;
        } else {
            unset($params[$this->queryParam]);
        }

        return $params;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCursorParams(string $cursorValue): array
    {
        $params = $this->buildBaseParams();

        unset($params[$this->queryParam]);
        $params[$this->cursorParam] = $cursorValue;

        return $params;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildBaseParams(): array
    {
        $params = $this->routeParams;

        if (QueryStringPolicy::Preserve === $this->queryStringPolicy) {
            // Route parameters are canonical: a preserved query parameter must
            // never override a path parameter of the current route.
            $query = array_diff_key($this->getCurrentQueryParams(), $this->getCurrentRouteParams());
            $params = array_merge($query, $params);
        }

        foreach ($this->excludedQueryParameters as $name) {
            unset($params[$name]);
        }

        return array_merge($params, $this->appends);
    }

    // -------------------------------------------------------------------------
    // URL generation (single path for explicit route, auto-detect, and fallback)
    // -------------------------------------------------------------------------

    /**
     * @param array<string, mixed> $params
     */
    private function generateUrl(array $params, bool $absolute = false): string
    {
        $referenceType = $absolute ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH;

        // 1. Explicit route: developer called ->withRoute('blog_list')
        if (null !== $this->route && null !== $this->urlGenerator) {
            return $this->appendFragment(
                $this->generateRoute($this->urlGenerator, $this->route, $params, $referenceType),
            );
        }

        // 2. Auto-detect route from current request.
        //    Merges params into the current route params so the UrlGenerator
        //    places {page} in the path when the route defines it.
        if (null === $this->basePath) {
            $url = $this->generateFromCurrentRoute($params, $referenceType);
            if (null !== $url) {
                return $this->appendFragment($url);
            }
        }

        // 3. Fallback: basePath (or raw pathInfo) + query string
        $request = $this->requestStack?->getCurrentRequest();
        $path = $this->basePath ?? $request?->getPathInfo() ?? '';
        if ($absolute && null === $request) {
            throw new RuntimeException('Cannot generate an absolute pagination URL without a Request or a Router route.');
        }
        if ($absolute) {
            $path = $request->getSchemeAndHttpHost().$path;
        }
        $query = http_build_query($params);
        $url = '' !== $query ? $path.'?'.$query : $path;

        return $this->appendFragment($url);
    }

    /**
     * Auto-detect the current route and generate a URL with merged params.
     *
     * Removes the pagination parameter from the current route params so that
     * the new value from $params takes over. The UrlGenerator decides whether
     * each param belongs in the path or query string based on the route definition.
     *
     * @param array<string, mixed> $params
     */
    private function generateFromCurrentRoute(array $params, int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): ?string
    {
        if (null === $this->urlGenerator) {
            return null;
        }

        $request = $this->requestStack?->getCurrentRequest();
        if (null === $request) {
            return null;
        }

        $routeName = $request->attributes->get('_route');
        if (!\is_string($routeName)) {
            return null;
        }

        $routeParams = $this->getCurrentRouteParams();

        // Remove the pagination param from current route params so $params controls it
        unset($routeParams[$this->queryParam]);

        return $this->generateRoute($this->urlGenerator, $routeName, array_merge($routeParams, $params), $referenceType);
    }

    /**
     * Generate a route URL, retrying with an explicit page 1 when the
     * route declares the page parameter as mandatory.
     *
     * Page 1 URLs omit the page parameter on purpose; a route like
     * /blog/{page} without a default would otherwise fail to generate its
     * first-page link.
     *
     * @param array<string, mixed> $params
     */
    private function generateRoute(UrlGeneratorInterface $urlGenerator, string $route, array $params, int $referenceType): string
    {
        try {
            return $urlGenerator->generate($route, $params, $referenceType);
        } catch (MissingMandatoryParametersException $exception) {
            if (isset($params[$this->queryParam])) {
                throw $exception;
            }

            return $urlGenerator->generate($route, [...$params, $this->queryParam => 1], $referenceType);
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function getCurrentRouteParams(): array
    {
        /** @var array<string, mixed>|null $routeParams */
        $routeParams = $this->requestStack?->getCurrentRequest()?->attributes->get('_route_params');

        return \is_array($routeParams) ? $routeParams : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function getCurrentQueryParams(): array
    {
        $request = $this->requestStack?->getCurrentRequest();
        if (null === $request) {
            return [];
        }

        $params = $request->query->all();
        unset($params[$this->queryParam]);

        return $params;
    }

    private function appendFragment(string $url): string
    {
        if (null === $this->fragment) {
            return $url;
        }

        return $url.'#'.$this->fragment;
    }
}
