<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\DataTable\Action\DropdownActionType;
use Symfony\UX\DataTable\Column\NumberColumnType;
use Symfony\UX\DataTable\DataTableType;
use Symfony\UX\DataTable\Exception\InvalidArgumentException;
use Symfony\UX\DataTable\Filter\NumberFilterType;

final class DataTableBuilderTest extends TestCase
{
    use DataTableTestTrait;

    public function testAddsAndRemovesColumnsAndFilters(): void
    {
        $builder = self::createFactory()->createBuilder(DataTableType::class, [], ['translation_domain' => 'admin'])
            ->addColumn('name')
            ->addColumn('price', NumberColumnType::class, ['sort' => true])
            ->addColumn('stock')
            ->removeColumn('stock')
            ->addFilter('name')
            ->addFilter('price', NumberFilterType::class)
            ->addFilter('stock')
            ->removeFilter('stock');

        self::assertTrue($builder->hasColumn('price'));
        self::assertFalse($builder->hasColumn('stock'));
        self::assertTrue($builder->hasFilter('price'));
        self::assertFalse($builder->hasFilter('stock'));
        self::assertSame('admin', $builder->getOptions()['translation_domain']);

        $dataTable = $builder->getDataTable();
        self::assertSame(['name', 'price'], array_keys($dataTable->getColumns()));
        self::assertSame(['name', 'price'], array_keys($dataTable->getFilters()));
        self::assertSame('filter', $dataTable->getFilterForm()?->getName());
        self::assertSame('admin', $dataTable->getFilterForm()->getConfig()->getOption('translation_domain'));
        self::assertSame(['name', 'price'], array_keys($dataTable->getFilterForm()->all()));
    }

    public function testAddsAndRemovesActions(): void
    {
        $dataTable = self::createFactory()->createBuilder(DataTableType::class, [['id' => 1]])
            ->addAction('create', options: ['href' => '/new'])
            ->addAction('import', options: ['href' => '/import'])
            ->removeAction('import')
            ->addRowAction('edit', options: ['href' => '/edit'])
            ->addRowAction('delete', options: ['href' => '/delete'])
            ->removeRowAction('delete')
            ->addBatchAction('archive', ['href' => '/archive'])
            ->addBatchAction('export', ['href' => '/export', 'method' => 'GET'])
            ->addBatchAction('delete', ['href' => '/delete'])
            ->removeBatchAction('delete')
            ->getDataTable();
        $view = $dataTable->createView();

        self::assertSame(['create'], array_keys($view->actions));
        self::assertSame(['edit'], array_keys($view->rows[0]->actions));
        self::assertSame(['archive', 'export'], array_keys($view->batchActions));
        self::assertSame('POST', $view->batchActions['archive']->vars['method']);
        self::assertSame('GET', $view->batchActions['export']->vars['method']);
    }

    public function testGivesEachDataTableItsOwnQuery(): void
    {
        $builder = self::createFactory()->createBuilder(DataTableType::class, [['name' => 'Chair'], ['name' => 'Table']])
            ->addColumn('name')
            ->addFilter('name');
        $filtered = $builder->getDataTable();
        $filtered->submit(['filter' => ['name' => 'tab']]);
        $filtered->getPagination();

        self::assertCount(2, $builder->getDataTable()->getPagination());
    }

    public function testCreatesActions(): void
    {
        $action = self::createFactory()->createBuilder(DataTableType::class, [])->createAction('edit', DropdownActionType::class, ['actions' => []]);

        self::assertSame('edit', $action->getName());
        self::assertSame([], $action->getOptions()['actions']);
    }

    public function testRejectsBatchActionsWithoutAFormMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The batch action "delete" must use the GET or POST method, as it submits a form.');

        self::createFactory()->createBuilder(DataTableType::class, [])->addBatchAction('delete', ['href' => '/delete', 'method' => 'DELETE']);
    }

    public function testHasNoFilterFormWithoutFilters(): void
    {
        self::assertNull(self::createFactory()->createBuilder(DataTableType::class, [])->getDataTable()->getFilterForm());
    }

    public function testSetsTheDefaultSort(): void
    {
        $dataTable = self::createFactory()->createBuilder(DataTableType::class, [])
            ->addColumn('name', options: ['sort' => true])
            ->addColumn('price', options: ['sort' => true])
            ->setDefaultSort(['price' => \SortDirection::Descending, 'name' => \SortDirection::Ascending])
            ->getDataTable();

        self::assertSame(['price' => \SortDirection::Descending, 'name' => \SortDirection::Ascending], $dataTable->getSort());
    }

    public function testRejectsADefaultSortOnAnUnsortableColumn(): void
    {
        $builder = self::createFactory()->createBuilder(DataTableType::class, [], name: 'products')
            ->addColumn('name')
            ->setDefaultSort(['name' => \SortDirection::Ascending]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The data table "products" cannot be sorted by "name": the column does not exist or is not sortable.');

        $builder->getDataTable();
    }
}
