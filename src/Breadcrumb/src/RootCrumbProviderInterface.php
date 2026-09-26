<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;

/**
 * Prepends application-wide crumbs to every trail, such as a "Home" or a section root.
 *
 * Providers are invoked on every main request, before the trail is stored, and whatever they yield is prepended in order.
 * Yielding nothing means "no root crumb for this route", which is how a provider opts a route hierarchy out.
 * Several providers can be registered, and they are applied in tag priority order.
 *
 * @author Romain Monteil <monteil.romain@gmail.com>
 */
#[AutoconfigureTag('ux_breadcrumb.root_crumb_provider')]
interface RootCrumbProviderInterface
{
    /**
     * @return iterable<Breadcrumb>
     */
    public function __invoke(string $route, Request $request): iterable;
}
