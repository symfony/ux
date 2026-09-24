<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\UX\Upload\Controller\UploadController;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class UploadRouteLoader
{
    public function __invoke(): RouteCollection
    {
        $routes = new RouteCollection();

        $directRoute = new Route('/');
        $directRoute->setDefaults(['_controller' => UploadController::class.'::direct']);
        $directRoute->setMethods(['POST']);
        $routes->add('ux_upload_direct', $directRoute);

        $initRoute = new Route('/init');
        $initRoute->setDefaults(['_controller' => UploadController::class.'::init']);
        $initRoute->setMethods(['POST']);
        $routes->add('ux_upload_init', $initRoute);

        $resumeRoute = new Route('/resume');
        $resumeRoute->setDefaults(['_controller' => UploadController::class.'::resume']);
        $resumeRoute->setMethods(['POST']);
        $routes->add('ux_upload_resume', $resumeRoute);

        $removeRoute = new Route('/remove');
        $removeRoute->setDefaults(['_controller' => UploadController::class.'::remove']);
        $removeRoute->setMethods(['DELETE']);
        $routes->add('ux_upload_remove', $removeRoute);

        $handleRoute = new Route('/{uploadId}');
        $handleRoute->setDefaults(['_controller' => UploadController::class.'::handle']);
        $handleRoute->setMethods(['GET', 'POST', 'PUT', 'DELETE']);
        $routes->add('ux_upload_handle', $handleRoute);

        return $routes;
    }
}
