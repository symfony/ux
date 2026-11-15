<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\LiveComponent;

use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\DataTable\DataTable;
use Symfony\UX\DataTable\DataTableView;
use Symfony\UX\DataTable\Exception\LogicException;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\TwigComponent\Attribute\PostMount;

/**
 * Sorts, filters and paginates a data table in a live component.
 *
 * Render it with {{ ux_data_table(this.dataTableView) }}.
 *
 * @author Simon Daigre <simon@daig.re>
 */
trait ComponentWithDataTableTrait
{
    #[LiveProp(writable: true, url: true)]
    public int $page = 1;

    /**
     * @var array<string, string>
     */
    #[LiveProp(url: true)]
    public array $sort = [];

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true, url: true)]
    public array $filter = [];

    /**
     * Hash of the filter of the previous request, to go back to the first page when it changes.
     *
     * @internal
     */
    #[LiveProp]
    public string $dataTableFilterHash = '';

    /**
     * Route of the page that first rendered the component, used by the links
     * instead of the internal route of the live component requests.
     */
    #[LiveProp]
    public ?string $dataTableRoute = null;

    /**
     * @var array<string, bool|float|int|string>
     */
    #[LiveProp]
    public array $dataTableRouteParameters = [];

    private ?DataTable $dataTable = null;

    private ?RequestStack $dataTableRequestStack = null;

    /**
     * Creates the data table, without submitting it.
     */
    abstract protected function createDataTable(): DataTable;

    /**
     * @internal
     */
    #[Required]
    public function setDataTableRequestStack(RequestStack $requestStack): void
    {
        $this->dataTableRequestStack = $requestStack;
    }

    /**
     * @internal
     */
    #[PostMount]
    public function initializeDataTable(): void
    {
        $request = $this->dataTableRequestStack?->getCurrentRequest();
        $route = $request?->attributes->get('_route');
        if (null === $this->dataTableRoute && \is_string($route) && !$request->attributes->has('_live_component')) {
            $this->dataTableRoute = $route;
            $this->dataTableRouteParameters = array_filter($request->attributes->all('_route_params'), \is_scalar(...));
        }

        $this->initializeFilter();
    }

    /**
     * Goes back to the first page when the filter changed; onUpdated hooks
     * do not run for nested models like "filter.q".
     *
     * @internal
     */
    #[PostHydrate]
    public function resetPageOnFilterChange(): void
    {
        if ($this->dataTableFilterHash !== $hash = self::hashFilter($this->filter)) {
            $this->dataTableFilterHash = $hash;
            $this->resetPage();
        }
    }

    public function getDataTable(): DataTable
    {
        if (null !== $this->dataTable) {
            return $this->dataTable;
        }

        $dataTable = $this->createDataTable();
        $options = $dataTable->getOptions();
        // The live props are bound to the URL parameters of the same name
        foreach (['page_parameter' => 'page', 'sort_parameter' => 'sort', 'filter_parameter' => 'filter'] as $option => $prop) {
            if ($prop !== $options[$option]) {
                throw new LogicException(\sprintf('The "%s" option of the data table "%s" must be "%s" to bind it to the "%3$s" live prop.', $option, $dataTable->getName(), $prop));
            }
        }

        if (null !== $this->dataTableRoute) {
            $dataTable->setRoute($this->dataTableRoute, $this->dataTableRouteParameters);
        }

        // The filter is empty until initialized with every field, then always submitted:
        // an empty value clears the default data of its field
        $dataTable->submit(array_filter([
            'page' => $this->page,
            'sort' => $this->sort,
            'filter' => $this->filter,
        ], static fn (mixed $value): bool => [] !== $value));

        return $this->dataTable = $dataTable;
    }

    public function getDataTableView(): DataTableView
    {
        $view = $this->getDataTable()->createView();
        $view->vars['live'] = true;
        // Nothing to reset while every filter is empty
        if ([] === array_filter($this->filter, static fn (mixed $value): bool => null !== $value && '' !== $value && [] !== $value)) {
            $view->vars['reset_url'] = null;
        }

        return $view;
    }

    #[LiveAction]
    public function sortBy(#[LiveArg] string $column): void
    {
        $direction = $this->getDataTable()->getSort()[$column] ?? null;
        $this->sort = [$column => \SortDirection::Ascending === $direction ? 'desc' : 'asc'];
        $this->resetPage();
    }

    #[LiveAction]
    public function goToPage(#[LiveArg] int $page): void
    {
        $this->page = max(1, $page);
        $this->dataTable = null;
    }

    #[LiveAction]
    public function resetFilters(): void
    {
        $this->filter = [];
        $this->resetPage();
        $this->initializeFilter();
    }

    private function resetPage(): void
    {
        $this->page = 1;
        $this->dataTable = null;
    }

    /**
     * Initializes every filter field, as the frontend can only update existing models.
     */
    private function initializeFilter(): void
    {
        if (null !== $form = $this->getDataTable()->getFilterForm()) {
            $this->filter = self::extractFilterValues($form->createView());
        }
        $this->dataTableFilterHash = self::hashFilter($this->filter);
    }

    /**
     * @param array<string, mixed> $filter
     */
    private static function hashFilter(array $filter): string
    {
        return hash('xxh128', serialize($filter));
    }

    /**
     * @return array<string, mixed>
     */
    private static function extractFilterValues(FormView $view): array
    {
        $values = [];
        foreach ($view->children as $name => $child) {
            $values[$name] = match (true) {
                $child->vars['compound'] && !($child->vars['expanded'] ?? false) => self::extractFilterValues($child),
                // The value of a checkbox is its "value" attribute, sent only when checked
                isset($child->vars['checked']) => $child->vars['checked'] ? $child->vars['value'] : null,
                default => $child->vars['value'],
            };
        }

        return $values;
    }
}
