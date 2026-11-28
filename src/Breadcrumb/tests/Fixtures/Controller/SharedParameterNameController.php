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
use Symfony\UX\Breadcrumb\Tests\Fixtures\Category;
use Symfony\UX\Breadcrumb\Tests\Fixtures\Product;

/**
 * Its URL parameter and its translation parameter share a name on purpose, but read different arguments.
 */
final class SharedParameterNameController
{
    #[Breadcrumb(
        label: 'crumb',
        route: 'product_view',
        parameters: ['slug' => new Expression('category.name')],
        translationParameters: ['slug' => new Expression('product.name')],
    )]
    public function __invoke(Product $product, Category $category): Response
    {
        return new Response();
    }
}
