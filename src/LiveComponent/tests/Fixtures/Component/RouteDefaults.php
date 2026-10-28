<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Component;

use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('route_defaults', route: 'route_defaults_live_route')]
final class RouteDefaults
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?string $route = null;

    #[LiveProp]
    public ?string $scope = null;

    #[LiveAction]
    public function capture(Request $request): void
    {
        $this->route = $request->attributes->get('_route');
        $this->scope = $request->attributes->get('_scope');
    }
}
