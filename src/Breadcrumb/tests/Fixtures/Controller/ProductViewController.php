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
use Symfony\UX\Breadcrumb\Tests\Fixtures\RouteName;

/**
 * A three-level trail exercising every kind of parameter at once: a query parameter
 * evaluated from a controller argument, an inherited route parameter, and ICU
 * translation parameters on the current page.
 */
#[Breadcrumb(
    label: 'product.index.breadcrumb',
    route: RouteName::ProductIndex->value,
    parameters: [
        'state' => new Expression('product.state'),
    ],
)]
#[Breadcrumb(
    label: 'product.view.breadcrumb',
    route: RouteName::ProductView->value,
    parameters: ['slug'],
    translationParameters: [
        'released_at' => new Expression('product.releasedAt'),
    ],
)]
final class ProductViewController
{
    public function __invoke(Product $product): Response
    {
        return new Response($product->name);
    }
}
