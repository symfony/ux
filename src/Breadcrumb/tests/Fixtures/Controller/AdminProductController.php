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

#[Breadcrumb(label: 'product.index.breadcrumb', route: 'admin_product_index')]
final class AdminProductController
{
    public function index(): Response
    {
        return new Response('index');
    }

    #[Breadcrumb(label: 'product.view.breadcrumb')]
    public function view(Product $product): Response
    {
        return new Response($product->name);
    }

    #[Breadcrumb(label: 'product.view.breadcrumb', route: 'admin_product_view', inheritedParameters: ['slug'])]
    #[Breadcrumb(label: 'product.edit.breadcrumb')]
    public function edit(Product $product): Response
    {
        return new Response($product->name);
    }
}
