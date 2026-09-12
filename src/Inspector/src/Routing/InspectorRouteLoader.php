<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Inspector\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\UX\Inspector\Controller\InspectorController;

/**
 * Declares the route serving the inspector script, and only when it is enabled.
 *
 * The collection is built at container-compile time from the "enabled" flag, so
 * an application importing "@UXInspectorBundle/config/routes.php" without a
 * when@dev guard still exposes nothing outside debug: the route is simply absent.
 * A statically declared route would serve the script in production instead, as
 * the controller resolver instantiates the class even with no service registered.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class InspectorRouteLoader
{
    public function __construct(private readonly bool $enabled)
    {
    }

    public function __invoke(): RouteCollection
    {
        $routes = new RouteCollection();
        if ($this->enabled) {
            $routes->add('_ux_inspector_script', new Route('/_ux/inspector.js', [
                '_controller' => InspectorController::class,
                '_stateless' => true,
            ], methods: ['GET', 'HEAD']));
        }

        return $routes;
    }
}
