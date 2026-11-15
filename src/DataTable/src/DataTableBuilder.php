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

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\DataTable\Action\Action;
use Symfony\UX\DataTable\Action\ActionTypeInterface;
use Symfony\UX\DataTable\Action\LinkActionType;
use Symfony\UX\DataTable\Column\Column;
use Symfony\UX\DataTable\Column\ColumnType;
use Symfony\UX\DataTable\Column\ColumnTypeInterface;
use Symfony\UX\DataTable\Exception\InvalidArgumentException;
use Symfony\UX\DataTable\Filter\Filter;
use Symfony\UX\DataTable\Filter\FilterTypeInterface;
use Symfony\UX\DataTable\Filter\TextFilterType;
use Symfony\UX\DataTable\Form\FilterFormType;
use Symfony\UX\DataTable\Query\QueryInterface;
use Symfony\UX\Pagination\PaginatorInterface;

/**
 * @phpstan-import-type DataTableOptions from DataTable
 * @phpstan-import-type ColumnOptions from Column
 * @phpstan-import-type FilterOptions from Filter
 *
 * @author Simon Daigre <simon@daig.re>
 */
final class DataTableBuilder
{
    /**
     * @var array<string, array{class-string<ColumnTypeInterface>, array<string, mixed>}>
     */
    private array $columns = [];

    /**
     * @var array<string, array{class-string<FilterTypeInterface>, array<string, mixed>}>
     */
    private array $filters = [];

    /**
     * @var array<string, \SortDirection>
     */
    private array $defaultSort = [];

    /**
     * @var array<string, Action>
     */
    private array $actions = [];

    /**
     * @var array<string, Action>
     */
    private array $rowActions = [];

    /**
     * @var array<string, Action>
     */
    private array $batchActions = [];

    /**
     * @param non-empty-list<DataTableTypeInterface> $types
     * @param DataTableOptions                       $options
     *
     * @internal use DataTableFactory::createBuilder()
     */
    public function __construct(
        private readonly string $name,
        private readonly array $types,
        private readonly array $options,
        private readonly QueryInterface $query,
        private readonly TypeRegistry $registry,
        private readonly FormFactoryInterface $formFactory,
        private readonly PaginatorInterface $paginator,
        private readonly ?UrlGeneratorInterface $urlGenerator = null,
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
     * @param class-string<ColumnTypeInterface> $type
     * @param array<string, mixed>              $options
     */
    public function addColumn(string $name, string $type = ColumnType::class, array $options = []): static
    {
        $this->columns[$name] = [$type, $options];

        return $this;
    }

    public function hasColumn(string $name): bool
    {
        return isset($this->columns[$name]);
    }

    public function removeColumn(string $name): static
    {
        unset($this->columns[$name]);

        return $this;
    }

    /**
     * @param class-string<FilterTypeInterface> $type
     * @param array<string, mixed>              $options
     */
    public function addFilter(string $name, string $type = TextFilterType::class, array $options = []): static
    {
        $this->filters[$name] = [$type, $options];

        return $this;
    }

    public function hasFilter(string $name): bool
    {
        return isset($this->filters[$name]);
    }

    public function removeFilter(string $name): static
    {
        unset($this->filters[$name]);

        return $this;
    }

    /**
     * Adds an action above the table.
     *
     * @param class-string<ActionTypeInterface> $type
     * @param array<string, mixed>              $options
     */
    public function addAction(string $name, string $type = LinkActionType::class, array $options = []): static
    {
        $this->actions[$name] = $this->createAction($name, $type, $options);

        return $this;
    }

    public function removeAction(string $name): static
    {
        unset($this->actions[$name]);

        return $this;
    }

    /**
     * Adds an action to each row, its closure options receiving the row.
     *
     * @param class-string<ActionTypeInterface> $type
     * @param array<string, mixed>              $options
     */
    public function addRowAction(string $name, string $type = LinkActionType::class, array $options = []): static
    {
        $this->rowActions[$name] = $this->createAction($name, $type, $options);

        return $this;
    }

    public function removeRowAction(string $name): static
    {
        unset($this->rowActions[$name]);

        return $this;
    }

    /**
     * Adds a link action submitting the identifiers of the checked rows, with the POST method by default.
     *
     * @param array<string, mixed> $options
     */
    public function addBatchAction(string $name, array $options = []): static
    {
        $this->batchActions[$name] = $action = $this->createAction($name, LinkActionType::class, $options + ['method' => 'POST']);
        if (!\in_array($action->getOptions()['method'], ['GET', 'POST'], true)) {
            throw new InvalidArgumentException(\sprintf('The batch action "%s" must use the GET or POST method, as it submits a form.', $name));
        }

        return $this;
    }

    public function removeBatchAction(string $name): static
    {
        unset($this->batchActions[$name]);

        return $this;
    }

    /**
     * Creates an action, e.g. for the "actions" option of a DropdownActionType.
     *
     * @param class-string<ActionTypeInterface> $type
     * @param array<string, mixed>              $options
     */
    public function createAction(string $name, string $type = LinkActionType::class, array $options = []): Action
    {
        [$types, $options] = $this->registry->resolve($type, ActionTypeInterface::class, $options);

        return new Action($name, $types, $options);
    }

    /**
     * @param array<string, \SortDirection> $sort the sort applied when none is submitted, by column name
     */
    public function setDefaultSort(array $sort): static
    {
        $this->defaultSort = $sort;

        return $this;
    }

    public function getDataTable(): DataTable
    {
        $columns = [];
        foreach ($this->columns as $name => [$type, $options]) {
            [$types, $resolved] = $this->registry->resolve($type, ColumnTypeInterface::class, $options);
            /** @var ColumnOptions $columnOptions the types allowed by the resolver */
            $columnOptions = $resolved;
            $columns[$name] = new Column($name, $types, $columnOptions, $this->reader);
        }

        foreach ($this->defaultSort as $name => $direction) {
            if (!($columns[$name] ?? null)?->isSortable()) {
                throw new InvalidArgumentException(\sprintf('The data table "%s" cannot be sorted by "%s": the column does not exist or is not sortable.', $this->name, $name));
            }
        }

        $filters = [];
        $filterForm = null;
        if ([] !== $this->filters) {
            $formBuilder = $this->formFactory->createNamedBuilder($this->options['filter_parameter'], FilterFormType::class, null, [
                'translation_domain' => $this->options['translation_domain'],
            ]);
            foreach ($this->filters as $name => [$type, $options]) {
                /** @var FilterOptions $filterOptions the types allowed by the resolver */
                $filterOptions = $this->registry->resolve($type, FilterTypeInterface::class, $options)[1];
                $filters[$name] = new Filter($name, $filterOptions);
                $filters[$name]->buildForm($formBuilder);
            }
            $filterForm = $formBuilder->getForm();
        }

        // Each data table sorts and filters its own copy of the query
        return new DataTable($this->name, $this->types, $this->options, $columns, $filters, $filterForm, clone $this->query, $this->paginator, $this->urlGenerator, $this->defaultSort, $this->actions, $this->rowActions, $this->batchActions, $this->reader);
    }
}
