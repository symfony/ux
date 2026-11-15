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
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\UX\DataTable\AbstractDataTableType;
use Symfony\UX\DataTable\DataTable;
use Symfony\UX\DataTable\DataTableBuilder;
use Symfony\UX\DataTable\DataTableView;
use Symfony\UX\DataTable\Exception\LogicException;
use Symfony\UX\DataTable\Filter\FilterType;
use Symfony\UX\DataTable\Tests\Fixtures\Entity\Product;
use Symfony\UX\DataTable\Tests\Fixtures\ProductDataTableType;
use Symfony\UX\DataTable\Tests\Fixtures\Status;

final class DataTableTest extends TestCase
{
    use DataTableTestTrait;

    public function testSubmitsThePageTheSortAndTheFilters(): void
    {
        $dataTable = self::create();
        $dataTable->submit(['page' => '2', 'sort' => ['price' => 'DESC'], 'filter' => ['q' => 'a'], 'tab' => 'all']);

        self::assertSame(2, $dataTable->getPage());
        self::assertSame(['price' => \SortDirection::Descending], $dataTable->getSort());
        self::assertSame('a', $dataTable->getFilterForm()?->get('q')->getData());
        self::assertSame(['page' => '2', 'sort' => ['price' => 'DESC'], 'filter' => ['q' => 'a'], 'tab' => 'all'], $dataTable->getParameters());
        self::assertSame(['Chair'], self::names($dataTable));
    }

    public function testKeepsTheDefaultsWithoutParameters(): void
    {
        $dataTable = self::create();
        $dataTable->submit([]);

        self::assertSame(1, $dataTable->getPage());
        self::assertSame(['name' => \SortDirection::Ascending], $dataTable->getSort());
        self::assertFalse($dataTable->getFilterForm()?->isSubmitted());
        self::assertSame(['Armchair', 'Chair'], self::names($dataTable));
    }

    public function testIgnoresInvalidParameters(): void
    {
        $dataTable = self::create();
        $dataTable->submit(['page' => ['x'], 'sort' => ['price' => 'up', 'status' => 'asc', 'unknown' => 'asc', 'name' => ['asc']], 'filter' => 'x']);

        self::assertSame(1, $dataTable->getPage());
        self::assertSame([], $dataTable->getSort());
        self::assertTrue($dataTable->getFilterForm()?->isSubmitted());

        $dataTable = self::create();
        $dataTable->submit(['page' => '-1', 'sort' => 'name']);

        self::assertSame(1, $dataTable->getPage());
        self::assertSame(['name' => \SortDirection::Ascending], $dataTable->getSort());
    }

    public function testIgnoresInvalidFilterFieldsOnly(): void
    {
        $dataTable = self::createFactory()->createBuilder(ProductDataTableType::class, self::products())
            ->addFilter('price', FilterType::class, ['form_type' => NumberType::class])
            ->getDataTable();
        $dataTable->submit(['filter' => ['price' => 'cheap', 'q' => 'chair']]);

        self::assertFalse($dataTable->getFilterForm()?->get('price')->isValid());
        self::assertSame(['Armchair', 'Chair'], self::names($dataTable));
    }

    public function testHandlesTheRequest(): void
    {
        $dataTable = self::create(self::urlGenerator());
        $request = Request::create('/shops/paris/products', parameters: ['page' => '2', 'tab' => 'all']);
        $request->attributes->add(['_route' => 'products', '_route_params' => ['shop' => 'paris']]);
        $dataTable->handleRequest($request);

        self::assertSame(2, $dataTable->getPage());
        $pagination = $dataTable->getPagination();
        self::assertSame('/shops/paris/products?tab=all&page=3', $pagination->getUrl(3));
        self::assertSame('/shops/paris/products?tab=all&sort%5Bprice%5D=asc', $dataTable->createView()->headers['price']->vars['sort_url']);
    }

    public function testHandlesARequestWithoutRoute(): void
    {
        $dataTable = self::create(self::urlGenerator());
        $dataTable->handleRequest(Request::create('/products', parameters: ['sort' => ['price' => 'asc']]));

        self::assertSame('?sort%5Bprice%5D=desc', $dataTable->createView()->headers['price']->vars['sort_url']);
    }

    public function testPaginatesWithTheOptions(): void
    {
        $dataTable = self::create(options: ['per_page' => 1, 'page_parameter' => 'p', 'lookahead' => true]);
        $dataTable->submit(['p' => 2]);
        $pagination = $dataTable->getPagination();

        self::assertSame(['Chair'], array_map(static fn (Product $product) => $product->name, $pagination->getItems()));
        self::assertSame('p', $pagination->getPageParameterName());
        self::assertNull($pagination->getTotalItems());
        self::assertSame($pagination, $dataTable->getPagination());
    }

    public function testCannotBeModifiedOncePaginated(): void
    {
        $dataTable = self::create(name: 'products');
        $dataTable->getPagination();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The data table "products" cannot be modified once paginated.');

        $dataTable->submit([]);
    }

    public function testCreatesTheView(): void
    {
        $dataTable = self::create(options: ['attr' => ['id' => 'products'], 'translation_domain' => 'admin', 'turbo_frame' => true]);
        $dataTable->submit(['sort' => ['price' => 'asc'], 'filter' => ['q' => 'chair', 'active' => ''], 'page' => 1, 'tab' => 'all']);
        $view = $dataTable->createView();

        self::assertSame($dataTable->getPagination(), $view->pagination);
        self::assertSame('filter', $view->filterForm?->vars['name']);
        self::assertSame([
            'name' => 'built_data_table',
            'attr' => ['id' => 'products'],
            'theme' => '@UXDataTable/theme/default.html.twig',
            'translation_domain' => 'admin',
            'turbo_frame' => true,
            'live' => false,
            'filter_url' => '?',
            'filter_hidden_fields' => ['sort[price]' => 'asc', 'tab' => 'all'],
            'reset_url' => '?sort%5Bprice%5D=asc&tab=all',
            'row_actions' => false,
            'batch' => false,
            'batch_form' => 'built_data_table-batch',
            'batch_parameter' => 'ids',
            'built' => true,
        ], $view->vars);

        self::assertSame(['name', 'price', 'active', 'status'], array_keys($view->headers));
        self::assertSame('admin', $view->headers['name']->vars['translation_domain']);
        self::assertNull($view->headers['name']->vars['sort_direction']);
        self::assertSame('?sort%5Bname%5D=asc&filter%5Bq%5D=chair&filter%5Bactive%5D=&tab=all', $view->headers['name']->vars['sort_url']);
        self::assertSame('asc', $view->headers['price']->vars['sort_direction']);
        self::assertSame('?sort%5Bprice%5D=desc&filter%5Bq%5D=chair&filter%5Bactive%5D=&tab=all', $view->headers['price']->vars['sort_url']);
        self::assertNull($view->headers['status']->vars['sort_url']);

        self::assertCount(2, $view->rows);
        self::assertSame('Chair', $view->rows[0]->cells['name']->vars['value']);
        self::assertSame('10.00', $view->rows[0]->cells['price']->vars['value']);
        self::assertSame('Published', $view->rows[1]->cells['status']->vars['value']);
        self::assertSame('admin', $view->rows[0]->cells['status']->vars['translation_domain']);
        self::assertSame([], $view->rows[0]->actions);
        self::assertNull($view->rows[0]->vars['identifier']);
    }

    public function testBuildsUrlsWhateverTheArgumentSeparator(): void
    {
        $separator = ini_set('arg_separator.output', '&amp;');
        try {
            $dataTable = self::create();
            $dataTable->submit(['sort' => ['price' => 'asc'], 'tab' => 'all']);
            $view = $dataTable->createView();
        } finally {
            ini_set('arg_separator.output', (string) $separator);
        }

        self::assertSame(['sort[price]' => 'asc', 'tab' => 'all'], $view->vars['filter_hidden_fields']);
        self::assertSame('?sort%5Bname%5D=asc&tab=all', $view->headers['name']->vars['sort_url']);
    }

    public function testCreatesTheViewsOfTheVisibleActions(): void
    {
        $dataTable = self::createFactory()->createBuilder(BuiltDataTableType::class, [['id' => 7, 'name' => 'Chair', 'active' => true], ['id' => 8, 'name' => 'Table', 'active' => false]], ['translation_domain' => 'admin'], 'products')
            ->addColumn('name')
            ->addAction('create', options: ['href' => '/new'])
            ->addAction('import', options: ['href' => '/import', 'visible' => false])
            ->addRowAction('edit', options: ['href' => static fn (array $row): string => '/edit/'.$row['id']])
            ->addRowAction('restore', options: ['href' => '/restore', 'visible' => static fn (array $row): bool => !$row['active']])
            ->addBatchAction('archive', ['href' => '/archive'])
            ->getDataTable();
        $view = $dataTable->createView();

        self::assertSame(['create'], array_keys($view->actions));
        self::assertSame('products-create', $view->actions['create']->vars['id']);
        self::assertSame('admin', $view->actions['create']->vars['translation_domain']);
        self::assertSame('products-batch-archive', $view->batchActions['archive']->vars['id']);
        self::assertTrue($view->vars['batch']);
        self::assertTrue($view->vars['row_actions']);
        self::assertSame('products-batch', $view->vars['batch_form']);

        self::assertSame(['edit'], array_keys($view->rows[0]->actions));
        self::assertSame('products-0-edit', $view->rows[0]->actions['edit']->vars['id']);
        self::assertSame('/edit/7', $view->rows[0]->actions['edit']->vars['href']);
        self::assertSame(7, $view->rows[0]->vars['identifier']);
        self::assertSame(['edit', 'restore'], array_keys($view->rows[1]->actions));
        self::assertSame('products-1-restore', $view->rows[1]->actions['restore']->vars['id']);
    }

    public function testHidesTheBatchAndRowActionsWhenNoneIsVisible(): void
    {
        $dataTable = self::createFactory()->createBuilder(BuiltDataTableType::class, [['name' => 'Chair', 'active' => true], ['name' => 'Table', 'active' => true]])
            ->addColumn('name')
            ->addRowAction('restore', options: ['href' => '/restore', 'visible' => static fn (array $row): bool => !$row['active']])
            ->addBatchAction('archive', ['href' => '/archive', 'visible' => false])
            ->getDataTable();
        $view = $dataTable->createView();

        self::assertFalse($view->vars['batch']);
        self::assertFalse($view->vars['row_actions']);
        // The identifier is not read, the rows having no "id"
        self::assertNull($view->rows[0]->vars['identifier']);
    }

    public function testCreatesTheViewWithoutFilters(): void
    {
        $dataTable = self::createFactory()->createBuilder(BuiltDataTableType::class, [])->getDataTable();
        $view = $dataTable->createView();

        self::assertNull($view->filterForm);
        self::assertNull($view->vars['reset_url']);
        self::assertSame([], $view->rows);
    }

    /**
     * @param array<string, mixed> $options
     */
    private static function create(?UrlGenerator $urlGenerator = null, array $options = [], ?string $name = null): DataTable
    {
        return self::createFactory($urlGenerator, new RequestStack())->create(BuiltDataTableType::class, self::products(), ['product' => true] + $options, $name);
    }

    /**
     * @return list<Product>
     */
    private static function products(): array
    {
        return [new Product('Chair', 10, status: Status::Draft), new Product('Table', 20, false), new Product('Armchair', 30, status: Status::Published)];
    }

    /**
     * @return list<string>
     */
    private static function names(DataTable $dataTable): array
    {
        return array_map(static fn (Product $product) => $product->name, $dataTable->getPagination()->getItems());
    }

    private static function urlGenerator(): UrlGenerator
    {
        $routes = new RouteCollection();
        $routes->add('products', new Route('/shops/{shop}/products'));

        return new UrlGenerator($routes, new RequestContext());
    }
}

/**
 * Builds the products data table when the "product" option is set, and marks its views.
 */
final class BuiltDataTableType extends AbstractDataTableType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('product', false);
    }

    public function buildDataTable(DataTableBuilder $builder, array $options): void
    {
        if ($options['product']) {
            new ProductDataTableType()->buildDataTable($builder, $options);
        }
    }

    public function buildView(DataTableView $view, DataTable $dataTable, array $options): void
    {
        $view->vars['built'] = true;
    }
}
