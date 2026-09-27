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

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;
use Symfony\UX\Breadcrumb\Tests\Fixtures\Product;

/**
 * Its only expression is the label, so `product` is pinned on the trail by the label alone.
 */
#[Breadcrumb(label: new Expression('product.name'))]
final class ProductNameController
{
    public function __invoke(Product $product): Response
    {
        return new Response($product->name);
    }
}
