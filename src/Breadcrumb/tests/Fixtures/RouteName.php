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

/**
 * Route names in a backed enum, the way the attribute's \BackedEnum support expects.
 */
enum RouteName: string
{
    case DashboardHome = 'dashboard_home';
    case ProductIndex = 'product_index';
    case ProductView = 'product_view';
}
