<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb\Tests\Fixtures;

use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;
use Symfony\UX\Breadcrumb\RootCrumbProviderInterface;

/**
 * A root crumb carrying an expression, which the listener must scan into the context
 * even though providers run after the controller's own crumbs are collected.
 */
final class ExpressionRootCrumbProvider implements RootCrumbProviderInterface
{
    public function __invoke(string $route, Request $request): iterable
    {
        if (str_starts_with($route, 'product_')) {
            yield new Breadcrumb(
                label: 'dashboard.home.breadcrumb',
                route: RouteName::ProductIndex->value,
                computedParameters: ['state' => 'product.state'],
            );
        }
    }
}
