<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\DataTable\Action\Action;
use Symfony\UX\DataTable\Action\ActionView;
use Symfony\UX\DataTable\Column\Column;
use Symfony\UX\DataTable\Exception\LogicException;
use Symfony\UX\DataTable\Filter\Filter;
use Symfony\UX\DataTable\Query\QueryInterface;
use Symfony\UX\Pagination\Pagination;
use Symfony\UX\Pagination\PaginatorInterface;

/**
 * @phpstan-type DataTableOptions array{theme: string, attr: array<string, mixed>, translation_domain: string|bool|null, page_parameter: string, sort_parameter: string, filter_parameter: string, per_page: positive-int|null, lookahead: bool, turbo_frame: bool, batch_identifier: string, batch_parameter: string, ...<string, mixed>}
 *
 * @author Simon Daigre <simon@daig.re>
 */
final class DataTable
{
    /**
     * @var array<string, mixed>
     */
    private array $parameters = [];

    private ?string $route = null;

    /**
     * @var array<string, mixed>
     */
    private array $routeParameters = [];

    private int $page = 1;

    /**
     * @var Pagination<mixed>|null
     */
    private ?Pagination $pagination = null;

    /**
     * @param non-empty-list<DataTableTypeInterface> $types
     * @param DataTableOptions                       $options
     * @param array<string, Column>                  $columns
     * @param array<string, Filter>                  $filters
     * @param array<string, \SortDirection>          $sort
     * @param array<string, Action>                  $actions
     * @param array<string, Action>                  $rowActions
     * @param array<string, Action>                  $batchActions
     *
     * @internal use DataTableFactory::create()
     */
    public function __construct(
        private readonly string $name,
        private readonly array $types,
        private readonly array $options,
        private readonly array $columns,
        private readonly array $filters,
        private readonly ?FormInterface $filterForm,
        private readonly QueryInterface $query,
        private readonly PaginatorInterface $paginator,
        private readonly ?UrlGeneratorInterface $urlGenerator = null,
        private array $sort = [],
        private readonly array $actions = [],
        private readonly array $rowActions = [],
        private readonly array $batchActions = [],
        private readonly PropertyReader $reader = new PropertyReader(),
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return array<string, Column>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return array<string, Filter>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getFilterForm(): ?FormInterface
    {
        return $this->filterForm;
    }

    /**
     * @return array<string, \SortDirection>
     */
    public function getSort(): array
    {
        return $this->sort;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * The submitted parameters, e.g. to link back to the data table in the same state.
     *
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Submits the query parameters and uses the route of the request for the links.
     */
    public function handleRequest(Request $request): void
    {
        $route = $request->attributes->get('_route');
        /** @var array<string, mixed> $routeParameters */
        $routeParameters = $request->attributes->all('_route_params');
        /** @var array<string, mixed> $parameters */
        $parameters = $request->query->all();
        $this->setRoute(\is_string($route) ? $route : null, $routeParameters);
        $this->submit($parameters);
    }

    /**
     * Sets the route of the links (sort, filters, pages), the current path otherwise.
     *
     * @param array<string, mixed> $parameters
     */
    public function setRoute(?string $route, array $parameters = []): void
    {
        $this->assertNotPaginated();
        $this->route = $route;
        $this->routeParameters = $parameters;
    }

    /**
     * Submits the page, the sort and the filters; other parameters are kept in the links.
     *
     * @param array<string, mixed> $parameters
     */
    public function submit(array $parameters): void
    {
        $this->assertNotPaginated();
        ['page_parameter' => $pageParameter, 'sort_parameter' => $sortParameter, 'filter_parameter' => $filterParameter] = $this->options;

        $this->parameters = $parameters;
        $this->page = filter_var($parameters[$pageParameter] ?? 1, \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;

        if (\is_array($sort = $parameters[$sortParameter] ?? null)) {
            $this->sort = [];
            foreach ($sort as $name => $direction) {
                $name = (string) $name;
                $direction = match (\is_string($direction) ? strtolower($direction) : null) {
                    'asc' => \SortDirection::Ascending,
                    'desc' => \SortDirection::Descending,
                    default => null,
                };
                if (null !== $direction && ($this->columns[$name] ?? null)?->isSortable()) {
                    $this->sort[$name] = $direction;
                }
            }
        }

        if (null !== $this->filterForm && isset($parameters[$filterParameter])) {
            $this->filterForm->submit(\is_array($parameters[$filterParameter]) ? $parameters[$filterParameter] : []);
        }
    }

    /**
     * Sorts, filters and paginates the source; the data table cannot be submitted anymore.
     *
     * @return Pagination<mixed>
     */
    public function getPagination(): Pagination
    {
        if (null !== $this->pagination) {
            return $this->pagination;
        }

        foreach ($this->sort as $name => $direction) {
            $this->query->sort($this->columns[$name]->getSortPath(), $direction);
        }

        if (null !== $form = $this->filterForm) {
            foreach ($this->filters as $name => $filter) {
                // An invalid field is ignored, the others still apply
                if (!$form->isSubmitted() || $form->get($name)->isValid()) {
                    $filter->apply($this->query, $form->get($name)->getData());
                }
            }
        }

        $builder = $this->paginator->query($this->query->getSource())
            ->pageParameter($this->options['page_parameter'])
            ->discardQueryString()
            ->queryParameters(array_diff_key($this->parameters, [$this->options['page_parameter'] => true]));

        if (null !== $this->options['per_page']) {
            $builder = $builder->perPage($this->options['per_page']);
        }

        if ($this->options['lookahead']) {
            $builder = $builder->lookahead();
        }

        if (null !== $this->route) {
            $builder = $builder->route($this->route, $this->routeParameters);
        }

        return $this->pagination = $builder->paginate($this->page);
    }

    public function createView(): DataTableView
    {
        $pagination = $this->getPagination();
        ['page_parameter' => $pageParameter, 'sort_parameter' => $sortParameter, 'filter_parameter' => $filterParameter, 'translation_domain' => $translationDomain] = $this->options;
        $parameters = array_diff_key($this->parameters, [$pageParameter => true]);
        $unfiltered = array_diff_key($parameters, [$filterParameter => true]);

        $view = new DataTableView($pagination, $this->filterForm?->createView());
        $view->vars = [
            'name' => $this->name,
            'attr' => $this->options['attr'],
            'theme' => $this->options['theme'],
            'translation_domain' => $translationDomain,
            'turbo_frame' => $this->options['turbo_frame'],
            'live' => false,
            'filter_url' => $this->generateUrl([]),
            'filter_hidden_fields' => self::flatten($unfiltered),
            'reset_url' => isset($this->parameters[$filterParameter]) ? $this->generateUrl($unfiltered) : null,
            'row_actions' => false,
            'batch' => false,
            'batch_form' => $this->name.'-batch',
            'batch_parameter' => $this->options['batch_parameter'],
        ];
        $view->actions = $this->createActionViews($this->actions, null, $this->name.'-');
        $view->batchActions = $this->createActionViews($this->batchActions, null, $this->name.'-batch-');

        foreach ($this->columns as $name => $column) {
            $direction = $this->sort[$name] ?? null;
            $view->headers[$name] = $column->createHeaderView([
                'translation_domain' => $translationDomain,
                'sort_direction' => null === $direction ? null : (\SortDirection::Ascending === $direction ? 'asc' : 'desc'),
                // A click sorts ascending, then toggles
                'sort_url' => $column->isSortable() ? $this->generateUrl([...$parameters, $sortParameter => [$name => \SortDirection::Ascending === $direction ? 'desc' : 'asc']]) : null,
            ]);
        }

        // The checkboxes and the actions column only for visible actions
        $view->vars['batch'] = $batch = [] !== $view->batchActions;
        $index = 0;
        foreach ($pagination as $row) {
            $view->rows[] = new RowView(
                array_map(static fn (Column $column) => $column->createCellView($row, ['translation_domain' => $translationDomain]), $this->columns),
                $actions = $this->createActionViews($this->rowActions, $row, $this->name.'-'.$index++.'-'),
                ['identifier' => $batch ? $this->reader->read($row, $this->options['batch_identifier']) : null],
            );
            $view->vars['row_actions'] = $view->vars['row_actions'] || [] !== $actions;
        }

        foreach ($this->types as $type) {
            $type->buildView($view, $this, $this->options);
        }

        return $view;
    }

    /**
     * @param array<string, Action> $actions
     *
     * @return array<string, ActionView> the visible actions
     */
    private function createActionViews(array $actions, mixed $row, string $idPrefix): array
    {
        $views = [];
        foreach ($actions as $name => $action) {
            $view = $action->createView($row, ['id' => $idPrefix.$name, 'translation_domain' => $this->options['translation_domain']]);
            if ($view->vars['visible']) {
                $views[$name] = $view;
            }
        }

        return $views;
    }

    private function assertNotPaginated(): void
    {
        if (null !== $this->pagination) {
            throw new LogicException(\sprintf('The data table "%s" cannot be modified once paginated.', $this->name));
        }
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function generateUrl(array $parameters): string
    {
        if (null !== $this->route && null !== $this->urlGenerator) {
            return $this->urlGenerator->generate($this->route, [...$parameters, ...$this->routeParameters]);
        }

        return '?'.http_build_query($parameters, '', '&');
    }

    /**
     * Flattens nested parameters into hidden form fields: ['sort' => ['name' => 'asc']] gives ['sort[name]' => 'asc'].
     *
     * @param array<string, mixed> $parameters
     *
     * @return array<string, string>
     */
    private static function flatten(array $parameters): array
    {
        $fields = [];
        foreach (explode('&', http_build_query($parameters, '', '&')) as $pair) {
            if ('' !== $pair) {
                [$name, $value] = explode('=', $pair, 2);
                $fields[urldecode($name)] = urldecode($value);
            }
        }

        return $fields;
    }
}
