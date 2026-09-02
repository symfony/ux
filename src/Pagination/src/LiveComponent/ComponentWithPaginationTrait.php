<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\LiveComponent;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Metadata\UrlMapping;
use Symfony\UX\Pagination\Exception\InvalidArgumentException;
use Symfony\UX\Pagination\Pagination;
use Symfony\UX\Pagination\PaginationBuilder;
use Symfony\UX\TwigComponent\Attribute\PostMount;

/**
 * Adds numbered pagination state and actions to a LiveComponent.
 *
 * The component owns its data source by returning a configured
 * PaginationBuilder from createPagination(). The trait only applies the
 * client-writable page state.
 *
 * Usage:
 *
 *     protected function createPagination(): PaginationBuilder
 *     {
 *         return $this->paginator->query(
 *             $this->products->createQueryBuilder('product'),
 *         );
 *     }
 *
 * @author Simon André <smn.andre@gmail.com>
 */
/** @phpstan-ignore-next-line trait.unused */
trait ComponentWithPaginationTrait
{
    #[LiveProp(writable: true, url: true, modifier: 'modifyPageProp')]
    public int $page = 1;

    /**
     * Route of the page that first rendered the component.
     *
     * Captured on the initial render so that pagination links keep pointing
     * to the page URL during LiveComponent re-renders, whose own requests
     * target the internal live component routes. An explicit route() call in
     * createPagination() takes precedence.
     */
    #[LiveProp]
    public ?string $paginationRoute = null;

    /**
     * @var array<string, bool|float|int|string>
     */
    #[LiveProp]
    public array $paginationRouteParams = [];

    private ?Pagination $paginationCache = null;

    private ?RequestStack $paginationRequestStack = null;

    /**
     * @internal
     */
    #[Required]
    public function setPaginationRequestStack(RequestStack $requestStack): void
    {
        $this->paginationRequestStack = $requestStack;
    }

    /**
     * @internal
     */
    #[PostMount]
    public function capturePaginationRoute(): void
    {
        if (null !== $this->paginationRoute) {
            return;
        }

        $request = $this->paginationRequestStack?->getCurrentRequest();
        $route = $request?->attributes->get('_route');
        if (!\is_string($route) || '' === $route || str_starts_with($route, 'ux_live_component')) {
            return;
        }

        $params = $request->attributes->get('_route_params');
        $this->paginationRoute = $route;
        $this->paginationRouteParams = array_filter(
            \is_array($params) ? $params : [],
            static fn (mixed $value): bool => \is_scalar($value),
        );
    }

    /**
     * Align the URL parameter with the page parameter configured on the builder.
     *
     * @internal
     */
    public function modifyPageProp(LiveProp $liveProp): LiveProp
    {
        $url = $liveProp->url();

        return $liveProp->withUrl(new UrlMapping(
            as: $this->createPagination()->getPageParameterName(),
            mapPath: $url instanceof UrlMapping && $url->mapPath,
        ));
    }

    /**
     * Return the current numbered pagination result.
     */
    public function getPagination(): Pagination
    {
        $this->assertPage($this->page);

        if (null !== $this->paginationCache
            && $this->paginationCache->getCurrentPage() === $this->page
        ) {
            return $this->paginationCache;
        }

        $builder = $this->createPagination();
        if (null !== $this->paginationRoute && !$builder->hasRoute()) {
            $builder = $builder->route($this->paginationRoute, $this->paginationRouteParams);
        }

        return $this->paginationCache = $builder->paginate(page: $this->page);
    }

    #[LiveAction]
    public function goToPage(#[LiveArg('page')] int $page): void
    {
        $this->assertPage($page);

        $this->page = $page;
        $this->paginationCache = null;
    }

    #[LiveAction]
    public function nextPage(): void
    {
        if ($this->getPagination()->hasNext()) {
            $this->goToPage($this->page + 1);
        }
    }

    #[LiveAction]
    public function previousPage(): void
    {
        if ($this->page > 1) {
            $this->goToPage($this->page - 1);
        }
    }

    /**
     * Reset pagination after changing a filter or sort order.
     */
    public function resetPage(): void
    {
        $this->page = 1;
        $this->paginationCache = null;
    }

    /**
     * Return link attributes for ux_pagination().
     *
     * @return \Closure(array{
     *     relation: 'previous'|'page'|'next',
     *     url: string,
     *     page: int|null,
     *     cursor: string|null,
     * }): array<string, bool|int|string|null>
     */
    public function getPaginationLinkAttributes(): \Closure
    {
        return static fn (array $link): array => [
            'data-action' => 'live#action:prevent',
            'data-live-action-param' => 'goToPage',
            'data-live-page-param' => $link['page'],
        ];
    }

    /**
     * Return a builder configured for this component's data source and filters.
     */
    abstract protected function createPagination(): PaginationBuilder;

    private function assertPage(int $page): void
    {
        if ($page < 1) {
            throw new InvalidArgumentException('Page must be greater than or equal to 1.');
        }
    }
}
