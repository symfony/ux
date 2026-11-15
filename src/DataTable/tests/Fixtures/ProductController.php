<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Tests\Fixtures;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\DataTable\Action\DropdownActionType;
use Symfony\UX\DataTable\Column\LinkColumnType;
use Symfony\UX\DataTable\Column\TemplateColumnType;
use Symfony\UX\DataTable\DataTableFactory;
use Symfony\UX\DataTable\Tests\Fixtures\Entity\Product;

final class ProductController extends AbstractController
{
    #[Route('/{_locale}/products', name: 'products', defaults: ['_locale' => 'en'])]
    public function products(Request $request, DataTableFactory $factory): Response
    {
        $builder = $factory->createBuilder(ProductDataTableType::class, Products::all(), [
            'per_page' => 2,
            'turbo_frame' => $request->query->has('turbo'),
            'translation_domain' => $request->query->has('untranslated') ? false : null,
            'batch_identifier' => 'name',
        ]);
        if ($request->query->has('actions')) {
            $builder
                ->addAction('create', options: ['href' => '/products/new', 'icon' => 'tabler:plus'])
                ->addRowAction('edit', options: ['href' => static fn (Product $product): string => '/products/'.$product->name.'/edit'])
                ->addRowAction('delete', options: [
                    'href' => static fn (Product $product): string => '/products/'.$product->name,
                    'method' => 'DELETE',
                    'csrf_token_id' => static fn (Product $product): string => 'delete-'.$product->name,
                    'confirmation' => true,
                ])
                ->addRowAction('more', DropdownActionType::class, ['actions' => [
                    $builder->createAction('archive', options: ['href' => '/products/archive', 'method' => 'POST']),
                    $builder->createAction('duplicate', options: ['href' => static fn (Product $product): string => '/products/'.$product->name.'/duplicate', 'method' => 'POST', 'confirmation' => 'Duplicate?']),
                ]])
                ->addBatchAction('export', ['href' => '/products/export', 'csrf_token_id' => 'export', 'confirmation' => 'Export the products?']);
        }

        $dataTable = $builder
            ->addColumn('category', options: ['property_path' => 'category?.name', 'header_attr' => ['class' => 'category'], 'sort' => 'category.name'])
            ->addColumn('show', LinkColumnType::class, ['getter' => static fn (Product $product) => 'Show', 'href' => static fn (string $value, Product $product) => '/products/'.$product->name])
            ->addColumn('badge', TemplateColumnType::class, ['template' => 'cell.html.twig', 'template_vars' => ['color' => 'blue'], 'label' => false, 'property_path' => false])
            ->addColumn('total', CurrencyColumnType::class, ['property_path' => 'price', 'decimals' => 2, 'header_attr' => ['class' => 'amount'], 'sort' => 'price'])
            ->getDataTable();
        $dataTable->handleRequest($request);

        return $this->render('products.html.twig', ['products' => $dataTable->createView()]);
    }

    #[Route('/live', name: 'live')]
    public function live(): Response
    {
        return $this->render('live.html.twig');
    }
}
