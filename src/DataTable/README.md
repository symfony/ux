# Symfony UX DataTable

**EXPERIMENTAL** This bundle is currently experimental and is likely to change,
possibly significantly, before its first stable release.

List data with sorting, filters, pagination and actions, server-side. A table
is a type built like a form: PHP declares its columns, filters and actions, a
Twig theme renders it, and the browser follows ordinary links and forms
without requiring JavaScript.

## Installation

```bash
composer require symfony/ux-data-table
```

## Usage

```php
// src/DataTable/ProductDataTableType.php
namespace App\DataTable;

use App\Entity\Product;
use Symfony\UX\DataTable\AbstractDataTableType;
use Symfony\UX\DataTable\Column\BooleanColumnType;
use Symfony\UX\DataTable\Column\NumberColumnType;
use Symfony\UX\DataTable\DataTableBuilder;
use Symfony\UX\DataTable\Filter\SearchFilterType;

final class ProductDataTableType extends AbstractDataTableType
{
    public function buildDataTable(DataTableBuilder $builder, array $options): void
    {
        $builder
            ->addColumn('name', options: ['sort' => true])
            ->addColumn('category', options: [
                'property_path' => 'category.name',
                'sort' => 'category.name',
            ])
            ->addColumn('price', NumberColumnType::class, [
                'decimals' => 2,
                'sort' => true,
            ])
            ->addColumn('active', BooleanColumnType::class)
            ->addFilter('search', SearchFilterType::class, [
                'fields' => ['name', 'category.name'],
            ])
            ->addRowAction('edit', options: [
                'href' => static fn (Product $product): string => '/products/'.$product->getId().'/edit',
            ])
            ->addRowAction('delete', options: [
                'href' => static fn (Product $product): string => '/products/'.$product->getId(),
                'method' => 'DELETE',
                'csrf_token_id' => 'delete',
                'confirmation' => true,
            ])
            ->setDefaultSort(['name' => \SortDirection::Ascending])
        ;
    }
}
```

```php
// src/Controller/ProductController.php
namespace App\Controller;

use App\DataTable\ProductDataTableType;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\DataTable\DataTableFactory;

final class ProductController extends AbstractController
{
    #[Route('/products', name: 'product_index')]
    public function __invoke(
        Request $request,
        DataTableFactory $factory,
        ProductRepository $products,
    ): Response {
        $dataTable = $factory->create(ProductDataTableType::class, $products->createQueryBuilder('product'), [
            'per_page' => 20,
        ]);
        $dataTable->handleRequest($request);

        return $this->render('product/index.html.twig', [
            'products' => $dataTable->createView(),
        ]);
    }
}
```

```twig
{# templates/product/index.html.twig #}
{{ ux_data_table(products) }}
```

The table reads its page, sort and filters from the query string, and keeps
them in the URLs it generates. Its source is an array, a Doctrine ORM query
builder or any `QueryInterface`. Set `turbo_frame: true` to sort, filter and
paginate inside a Turbo frame, or use `ComponentWithDataTableTrait` to render
it in a Live component.

## What it provides

- Columns: text, number, boolean, date time, enum, link and template, and your
  own types extending them
- Filters: search, text, number, date, boolean, choice and enum, or any form
  type with an `apply` closure
- Global, row and batch actions: links, or forms with any method and a CSRF
  token, behind a confirmation in a native `<dialog>`, grouped in dropdowns
- Pagination through [UX Pagination](../Pagination/README.md)
- Twig themes for plain HTML, Bootstrap 5 and Tabler, each block overridable
  like a form theme
- Translations in 12 languages

## Documentation

The documentation will be written once the component's API is settled.

**This repository is a READ-ONLY subtree split.**

## Resources

- [Report issues](https://github.com/symfony/ux/issues) and
  [send Pull Requests](https://github.com/symfony/ux/pulls)
  in the [main Symfony UX repository](https://github.com/symfony/ux)
