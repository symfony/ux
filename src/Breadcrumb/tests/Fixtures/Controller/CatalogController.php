<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb\Tests\Fixtures\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;
use Symfony\UX\Breadcrumb\Tests\Fixtures\Product;

#[Breadcrumb(label: 'product.index.breadcrumb', route: 'catalog_index')]
final class CatalogController
{
    public function index(): Response
    {
        return new Response('index');
    }

    #[Breadcrumb(
        label: 'product.view.breadcrumb',
        route: 'catalog_view',
        inheritedParameters: ['slug'],
        translationParameters: ['released_at' => 'product.releasedAt'],
    )]
    public function view(Product $product): Response
    {
        return new Response($product->name);
    }

    #[Breadcrumb(label: 'product.edit.breadcrumb', route: 'catalog_edit', inheritedParameters: ['slug'], parent: 'catalog_view')]
    public function edit(Product $product): Response
    {
        return new Response($product->name);
    }

    #[Breadcrumb(label: 'product.history.breadcrumb', parent: [self::class, 'edit'])]
    public function history(Product $product): Response
    {
        return new Response($product->name);
    }
}
