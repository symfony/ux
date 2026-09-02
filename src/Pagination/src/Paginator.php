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

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Pagination\Adapter\CallablePaginationAdapter;
use Symfony\UX\Pagination\Adapter\PaginationAdapterInterface;
use Symfony\UX\Pagination\Cursor\CursorCodecInterface;
use Symfony\UX\Pagination\Exception\InvalidArgumentException;
use Symfony\UX\Pagination\Exception\RuntimeException;
use Symfony\UX\Pagination\Navigation\NavigationMode;

/**
 * The pagination service.
 *
 * Type-hint PaginatorInterface in your controller:
 *
 *     public function list(PaginatorInterface $paginator): Response
 *     {
 *         $posts = $paginator->paginate($queryBuilder);
 *         // ...
 *     }
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class Paginator implements PaginatorInterface
{
    /** @var list<PaginationAdapterInterface> */
    private readonly array $adapters;

    /**
     * @param iterable<PaginationAdapterInterface> $adapters
     */
    public function __construct(
        iterable $adapters,
        private readonly ?RequestStack $requestStack = null,
        private readonly ?UrlGeneratorInterface $urlGenerator = null,
        private readonly ?PaginationInfoFormatter $infoFormatter = null,
        private readonly int $defaultPerPage = 20,
        private readonly string $defaultPageParam = 'page',
        private readonly string $defaultCursorParam = 'cursor',
        private readonly ?CursorCodecInterface $cursorCodec = null,
        private readonly int $defaultMaxOffset = 100_000,
        private readonly NavigationMode $defaultNavigationMode = NavigationMode::Sliding,
        private readonly int $defaultNavigationSize = 5,
    ) {
        $resolved = [];
        foreach ($adapters as $adapter) {
            $resolved[] = $adapter;
        }
        $this->adapters = $resolved;
    }

    /**
     * @template T
     *
     * @param array<T>|mixed $source
     *
     * @return ($source is array<T> ? Pagination<T> : Pagination<mixed>)
     */
    public function paginate(mixed $source, ?int $page = null, ?int $perPage = null): Pagination
    {
        /** @var Pagination<T> $pagination */
        $pagination = $this->applyPerPage($this->query($source), $perPage)->paginate($page);

        return $pagination;
    }

    /**
     * @template T
     *
     * @param callable(int, int): list<T> $slice
     * @param callable(): int             $count
     */
    public function fromCallbacks(callable $slice, callable $count): PaginationBuilder
    {
        $slice = \Closure::fromCallable($slice);
        $count = \Closure::fromCallable($count);

        return new PaginationBuilder(
            source: null,
            adapters: [],
            requestStack: $this->requestStack,
            urlGenerator: $this->urlGenerator,
            infoFormatter: $this->infoFormatter,
            defaultPerPage: $this->defaultPerPage,
            defaultPageParam: $this->defaultPageParam,
            adapter: new CallablePaginationAdapter($slice, $count),
            defaultCursorParam: $this->defaultCursorParam,
            defaultMaxOffset: $this->defaultMaxOffset,
            defaultNavigationMode: $this->defaultNavigationMode,
            defaultNavigationSize: $this->defaultNavigationSize,
        );
    }

    public function query(mixed $source): PaginationBuilder
    {
        return new PaginationBuilder(
            source: $source,
            adapters: $this->adapters,
            requestStack: $this->requestStack,
            urlGenerator: $this->urlGenerator,
            infoFormatter: $this->infoFormatter,
            defaultPerPage: $this->defaultPerPage,
            defaultPageParam: $this->defaultPageParam,
            defaultCursorParam: $this->defaultCursorParam,
            defaultMaxOffset: $this->defaultMaxOffset,
            defaultNavigationMode: $this->defaultNavigationMode,
            defaultNavigationSize: $this->defaultNavigationSize,
        );
    }

    public function cursor(mixed $source): CursorPaginationBuilder
    {
        if (null === $this->cursorCodec) {
            throw new RuntimeException('Cursor pagination requires a CursorCodecInterface configured with the application secret.');
        }

        return new CursorPaginationBuilder(
            source: $source,
            adapters: $this->adapters,
            codec: $this->cursorCodec,
            requestStack: $this->requestStack,
            urlGenerator: $this->urlGenerator,
            infoFormatter: $this->infoFormatter,
            defaultPerPage: $this->defaultPerPage,
            defaultPageParam: $this->defaultPageParam,
            defaultCursorParam: $this->defaultCursorParam,
        );
    }

    private function applyPerPage(PaginationBuilder $builder, ?int $perPage): PaginationBuilder
    {
        if (null === $perPage) {
            return $builder;
        }

        if ($perPage < 1) {
            throw new InvalidArgumentException('perPage must be >= 1.');
        }

        return $builder->perPage($perPage);
    }
}
