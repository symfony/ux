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
 * The reference shape: branch on the route name, and yield nothing for the route
 * hierarchies that deliberately have no root crumb.
 */
final class RootCrumbProvider implements RootCrumbProviderInterface
{
    private const string DASHBOARD_PREFIX = 'dashboard_';
    private const string DASHBOARD_HOME = 'dashboard_home';

    public function __invoke(string $route, Request $request): iterable
    {
        // Every dashboard page but the home page itself, which *is* the root crumb.
        if (str_starts_with($route, self::DASHBOARD_PREFIX) && self::DASHBOARD_HOME !== $route) {
            yield new Breadcrumb(
                label: 'dashboard.home.breadcrumb',
                route: RouteName::DashboardHome->value,
            );
        }

        if (str_starts_with($route, 'product_')) {
            yield new Breadcrumb(
                label: 'dashboard.home.breadcrumb',
                route: RouteName::DashboardHome->value,
            );
        }
    }
}
