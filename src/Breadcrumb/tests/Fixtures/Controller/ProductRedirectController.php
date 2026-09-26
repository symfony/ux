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

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;
use Symfony\UX\Breadcrumb\Tests\Fixtures\Product;
use Symfony\UX\Breadcrumb\Tests\Fixtures\RouteName;

/**
 * The laziness regression guard: declares crumbs that dereference an entity, then
 * redirects without rendering anything. Nothing may be translated or generated, and
 * the trail's context must hold only `product`, not `$request`.
 */
#[Breadcrumb(
    label: 'product.index.breadcrumb',
    route: RouteName::ProductIndex->value,
)]
#[Breadcrumb(
    label: 'product.view.breadcrumb',
    translationParameters: [
        'name' => 'product.name',
    ],
)]
final class ProductRedirectController
{
    public function __invoke(Product $product, Request $request): Response
    {
        return new RedirectResponse('/products');
    }
}
