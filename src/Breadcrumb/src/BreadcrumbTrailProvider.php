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

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Gives a controller access to the trail, to append a crumb whose label is only known at runtime.
 *
 * Reads the main request, not the current one: the listener only collects there, so a {{ render(controller(...)) }} fragment would otherwise see nothing.
 *
 * @author Romain Monteil <monteil.romain@gmail.com>
 */
final class BreadcrumbTrailProvider
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly string $requestAttribute = BreadcrumbTrail::ATTRIBUTE,
    ) {
    }

    public function getTrail(): ?BreadcrumbTrail
    {
        $trail = $this->requestStack->getMainRequest()?->attributes->get($this->requestAttribute);

        return $trail instanceof BreadcrumbTrail ? $trail : null;
    }
}
