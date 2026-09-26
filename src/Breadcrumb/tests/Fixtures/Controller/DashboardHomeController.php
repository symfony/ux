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

/**
 * Declares no crumb of its own: the root provider's lone crumb is the intended
 * breadcrumb of a section home page.
 */
final class DashboardHomeController
{
    public function __invoke(): Response
    {
        return new Response('dashboard');
    }
}
