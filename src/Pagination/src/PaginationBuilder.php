<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination;

use Symfony\Component\HttpFoundation\Exception\UnexpectedValueException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Pagination\Adapter\LookaheadAdapterInterface;
use Symfony\UX\Pagination\Adapter\OffsetAdapterInterface;
use Symfony\UX\Pagination\Adapter\PaginationAdapterInterface;
use Symfony\UX\Pagination\Exception\InvalidArgumentException;
use Symfony\UX\Pagination\Navigation\NavigationMode;
use Symfony\UX\Pagination\Navigation\PaginationUrlGenerator;

/**
 * Immutable builder for advanced pagination configuration.
 *
 * Every method returns a new instance (clone + modify).
 *
 * Usage:
 *     $paginator->query($source)
 *         ->perPage(25)
 *         ->sliding(size: 7)
 *         ->preserveQueryString()
 *         ->paginate(page: $page);
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class PaginationBuilder
{
    private int $perPage = 20;
    private NavigationMode $mode;
    private int $modeParameter;
    private PaginationUrlGenerator $paginationUrlGenerator;
    private bool $lookahead = false;
    private int|\Closure|null $total = null;
    private bool $throwOnOutOfRange = false;
    private int $maxOffset;

    /**
     * @param iterable<PaginationAdapterInterface> $adapters
     */
    public function __construct(
        private readonly mixed $source,
        private readonly iterable $adapters,
        private readonly ?RequestStack $requestStack = null,
        ?UrlGeneratorInterface $urlGenerator = null,
        private readonly ?PaginationInfoFormatter $infoFormatter = null,
        int $defaultPerPage = 20,
        string $defaultPageParam = 'page',
        private readonly ?PaginationAdapterInterface $adapter = null,
        string $defaultCursorParam = 'cursor',
        int $defaultMaxOffset = 100_000,
        NavigationMode $defaultNavigationMode = NavigationMode::Sliding,
        int $defaultNavigationSize = 5,
    ) {
        if (\PHP_INT_MAX === $defaultPerPage) {
            throw new InvalidArgumentException('perPage must be less than PHP_INT_MAX.');
        }

        $this->perPage = $defaultPerPage;
        $this->maxOffset = $defaultMaxOffset;
        $this->mode = $defaultNavigationMode;
        $this->modeParameter = $defaultNavigationSize;
        $this->paginationUrlGenerator = new PaginationUrlGenerator(
            queryParam: $defaultPageParam,
            requestStack: $requestStack,
            urlGenerator: $urlGenerator,
            cursorParam: $defaultCursorParam,
        );
    }

    /**
     * Display a window containing at most $size consecutive pages.
     */
    /**
     * @param int<1, max> $size
     */
    public function sliding(int $size = 5): self
    {
        if ($size < 1) {
            throw new InvalidArgumentException('size must be >= 1.');
        }

        $clone = clone $this;
        $clone->mode = NavigationMode::Sliding;
        $clone->modeParameter = $size;

        return $clone;
    }

    /**
     * @param int<1, max> $size
     */
    public function fixed(int $size = 10): self
    {
        if ($size < 1) {
            throw new InvalidArgumentException('size must be >= 1.');
        }

        $clone = clone $this;
        $clone->mode = NavigationMode::Fixed;
        $clone->modeParameter = $size;

        return $clone;
    }

    /**
     * @param int<1, max> $maxPages
     */
    public function full(int $maxPages = 500): self
    {
        if ($maxPages < 1) {
            throw new InvalidArgumentException('maxPages must be >= 1.');
        }

        $clone = clone $this;
        $clone->mode = NavigationMode::Full;
        $clone->modeParameter = $maxPages;

        return $clone;
    }

    /**
     * @param int<1, max> $items
     */
    public function perPage(int $items): self
    {
        if ($items < 1) {
            throw new InvalidArgumentException('perPage must be >= 1.');
        }
        if (\PHP_INT_MAX === $items) {
            throw new InvalidArgumentException('perPage must be less than PHP_INT_MAX.');
        }

        $clone = clone $this;
        $clone->perPage = $items;

        return $clone;
    }

    /**
     * Set the largest SQL/API offset accepted by this pagination.
     *
     * Prefer cursor pagination for large, stably ordered datasets.
     */
    /**
     * @param int<0, max> $max
     */
    public function maxOffset(int $max): self
    {
        if ($max < 0) {
            throw new InvalidArgumentException('maxOffset must be >= 0.');
        }

        $clone = clone $this;
        $clone->maxOffset = $max;

        return $clone;
    }

    public function pageParameter(string $name): self
    {
        $clone = clone $this;
        $clone->paginationUrlGenerator = $this->paginationUrlGenerator->withQueryParameter($name);

        return $clone;
    }

    public function getPageParameterName(): string
    {
        return $this->paginationUrlGenerator->getQueryParameterName();
    }

    /**
     * Whether an explicit route was configured with route().
     */
    public function hasRoute(): bool
    {
        return null !== $this->paginationUrlGenerator->getRouteName();
    }

    /**
     * @param array<string, mixed> $params
     */
    public function route(string $route, array $params = []): self
    {
        $clone = clone $this;
        $clone->paginationUrlGenerator = $this->paginationUrlGenerator->withRoute($route, $params);

        return $clone;
    }

    /**
     * @param array<string, mixed> $params
     */
    public function queryParameters(array $params): self
    {
        $clone = clone $this;
        $clone->paginationUrlGenerator = $this->paginationUrlGenerator->withQueryParameters($params);

        return $clone;
    }

    public function preserveQueryString(): self
    {
        $clone = clone $this;
        $clone->paginationUrlGenerator = $this->paginationUrlGenerator->withQueryString();

        return $clone;
    }

    public function discardQueryString(): self
    {
        $clone = clone $this;
        $clone->paginationUrlGenerator = $this->paginationUrlGenerator->withoutQueryString();

        return $clone;
    }

    public function excludeQueryParameters(string ...$names): self
    {
        $clone = clone $this;
        $clone->paginationUrlGenerator = $this->paginationUrlGenerator->withoutQueryParameters(...$names);

        return $clone;
    }

    public function fragment(string $fragment): self
    {
        $clone = clone $this;
        $clone->paginationUrlGenerator = $this->paginationUrlGenerator->withFragment($fragment);

        return $clone;
    }

    public function path(string $path): self
    {
        $clone = clone $this;
        $clone->paginationUrlGenerator = $this->paginationUrlGenerator->withPath($path);

        return $clone;
    }

    public function lookahead(): self
    {
        $clone = clone $this;
        $clone->lookahead = true;

        return $clone;
    }

    /**
     * Override the total number of items.
     *
     * The callable is evaluated lazily and at most once. This is useful for
     * aggregate queries whose total cannot be inferred by the adapter.
     *
     * @param int|(callable(): int) $total
     */
    public function total(int|callable $total): self
    {
        if (\is_int($total) && $total < 0) {
            throw new InvalidArgumentException('Total must be greater than or equal to 0.');
        }

        $clone = clone $this;
        $clone->total = \is_int($total) ? $total : \Closure::fromCallable($total);

        return $clone;
    }

    /**
     * Throw a 404 if the current page exceeds total pages.
     *
     * Triggers a COUNT query to determine the total.
     */
    public function throwOnOutOfRange(): self
    {
        $clone = clone $this;
        $clone->throwOnOutOfRange = true;

        return $clone;
    }

    /**
     * @return Pagination<mixed>
     *
     * When $page is null, resolve it from the current Request. Passing an
     * explicit page keeps pagination independent from RequestStack.
     */
    public function paginate(?int $page = null): Pagination
    {
        if ($this->lookahead && null !== $this->total) {
            throw new InvalidArgumentException('lookahead() cannot be combined with total(): choose lookahead navigation without an exact total, or numbered pagination with a total.');
        }
        if ($this->lookahead && $this->throwOnOutOfRange) {
            throw new InvalidArgumentException('lookahead() cannot be combined with throwOnOutOfRange(): detecting an out-of-range page requires an exact total.');
        }

        if (null === $page) {
            $page = $this->resolvePageFromRequest();
        }

        if ($page < 1) {
            throw new InvalidArgumentException('Page must be >= 1.');
        }

        $adapter = $this->resolveAdapter();
        $pagination = new Pagination(
            source: $this->source,
            adapter: $adapter,
            currentPage: $page,
            perPage: $this->perPage,
            paginationUrlGenerator: $this->paginationUrlGenerator,
            navigationMode: $this->mode,
            navigationModeParameter: $this->modeParameter,
            lookahead: $this->lookahead,
            total: $this->total,
            infoFormatter: $this->infoFormatter,
            maxOffset: $this->maxOffset,
        );

        if ($this->throwOnOutOfRange) {
            $pagination->throwOnOutOfRange();
        }

        return $pagination;
    }

    private function resolvePageFromRequest(): int
    {
        $request = $this->requestStack?->getCurrentRequest();
        if (null === $request) {
            return 1;
        }

        $queryParam = $this->paginationUrlGenerator->getQueryParameterName();
        $parameters = $request->attributes->has($queryParam)
            ? $request->attributes
            : $request->query;

        if (!$parameters->has($queryParam)) {
            return 1;
        }

        try {
            $page = $parameters->getInt($queryParam);
        } catch (UnexpectedValueException $exception) {
            throw new BadRequestHttpException(\sprintf('The pagination parameter "%s" must be a positive integer.', $queryParam), $exception);
        }

        if ($page < 1) {
            throw new BadRequestHttpException(\sprintf('The pagination parameter "%s" must be a positive integer.', $queryParam));
        }

        return $page;
    }

    private function resolveAdapter(): PaginationAdapterInterface
    {
        $capability = $this->lookahead ? LookaheadAdapterInterface::class : OffsetAdapterInterface::class;

        if (null !== $this->adapter) {
            if (!$this->adapter instanceof $capability) {
                throw new InvalidArgumentException(\sprintf('The adapter "%s" does not support "%s" pagination. Implement "%s".', $this->adapter::class, $this->lookahead ? 'lookahead' : 'offset', $capability));
            }

            return $this->adapter;
        }

        foreach ($this->adapters as $adapter) {
            if ($adapter instanceof $capability && $adapter->supports($this->source)) {
                return $adapter;
            }
        }

        throw new InvalidArgumentException(\sprintf('No "%s" pagination adapter found for source of type "%s". Register an adapter implementing "%s".', $this->lookahead ? 'lookahead' : 'offset', get_debug_type($this->source), $capability));
    }
}
