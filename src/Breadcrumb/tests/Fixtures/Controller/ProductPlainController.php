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

/**
 * Receives an argument but declares no expression of its own, so the only crumb that
 * can pin `product` on the trail is one a root provider supplies.
 */
#[Breadcrumb(label: 'product.view.breadcrumb')]
final class ProductPlainController
{
    public function __invoke(Product $product): Response
    {
        return new Response($product->name);
    }
}
